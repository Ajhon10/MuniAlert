import { reactive, readonly, watch } from 'vue';
import { api } from './api';
import { notify } from './feedback';
import { reading, restoreReadingScope } from './accessibility';
import type { ReadingPreferences, User } from '../types';

const state = reactive({ signedIn: false, saving: false, error: '' });
export const readingAccount = readonly(state);
let accountId: number | null = null; let epoch = 0; let hydrating = false;
type Save = { accountId: number; epoch: number; preferences: ReadingPreferences };
let pending: Save | null = null; let running: Promise<void> | null = null;
let timer: ReturnType<typeof setTimeout> | undefined;
function dirty(id: number, value?: boolean): boolean {
  try {
    const key = `munialert-reading-pending-${id}`;
    if (value === true) localStorage.setItem(key, '1'); else if (value === false) localStorage.removeItem(key);
    return localStorage.getItem(key) === '1';
  } catch { return false; }
}
export function bindReadingAccount(user: User | null) {
  epoch++; accountId = user?.id ?? null; pending = null; clearTimeout(timer);
  hydrating = true; restoreReadingScope(accountId, user && !dirty(user.id) ? user.reading_preferences : undefined); hydrating = false;
  state.signedIn = !!user; state.saving = false; state.error = '';
  if (user && dirty(user.id)) retryReadingSave();
}
export function retryReadingSave() {
  if (accountId == null) return;
  dirty(accountId, true); state.saving = true; state.error = '';
  pending = { accountId, epoch, preferences: { ...reading } };
  clearTimeout(timer); timer = setTimeout(() => void flushReadingSave(), 350);
}
export function flushReadingSave(): Promise<void> {
  clearTimeout(timer);
  if (running) return running;
  running = (async () => {
    while (pending) {
      const job = pending; pending = null;
      if (job.epoch !== epoch) continue;
      try {
        await api('/api/preferences/reading', { method: 'PUT', body: JSON.stringify({ account_id: job.accountId, preferences: job.preferences }) });
        if (job.epoch === epoch) { state.error = ''; if (!pending) dirty(job.accountId, false); }
      } catch {
        if (job.epoch === epoch) { state.error = 'Saved on this device. Account sync failed; retry when connected.'; notify(state.error, 'error'); }
      }
    }
  })().finally(() => { running = null; state.saving = !!pending; if (pending) void flushReadingSave(); });
  return running;
}
watch(reading, () => { if (!hydrating) retryReadingSave(); }, { deep: true, flush: 'sync' });
window.addEventListener('online', () => { if (accountId != null && dirty(accountId)) retryReadingSave(); });
