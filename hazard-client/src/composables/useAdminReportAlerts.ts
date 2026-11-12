import { onBeforeUnmount, watch, type Ref } from 'vue';
import type { Notice, User } from '../types';
import { bindAdminSounds, playHazardAlert } from '../services/adminSounds';
import { notify } from '../services/feedback';
export function useAdminReportAlerts(user: Ref<User | null>, notices: Ref<Notice[]>, demo: Ref<boolean>, ready: Ref<boolean>) {
  let id: number | null = null; let baseline = false; const seen = new Set<string>();
  watch([() => user.value?.id, () => user.value?.role, demo], () => { id = user.value?.role === 'admin' && !demo.value ? user.value.id : null; baseline = false; seen.clear(); bindAdminSounds(id); }, { immediate: true, flush: 'sync' });
  watch([notices, ready], ([items, available]) => {
    if (id == null || !available) return;
    let fresh = 0;
    for (const item of [...items].reverse()) {
      if (seen.has(item.id)) continue; seen.add(item.id);
      if (!baseline || item.read_at || item.type !== 'report_submitted') continue;
      const key = `munialert-report-alerts-${id}`; let handled = false;
      try { const stored = JSON.parse(localStorage.getItem(key) || '[]'); const history: string[] = Array.isArray(stored) ? stored.filter(item => typeof item === 'string').slice(-199) : []; handled = history.includes(item.id); if (!handled) localStorage.setItem(key, JSON.stringify([...history, item.id])); } catch {}
      if (!handled) { playHazardAlert(item.data.category_id || 'other', item.data.assistance_needed); fresh++; }
    }
    if (fresh) notify(`${fresh} new hazard report${fresh === 1 ? '' : 's'} received. Open Notifications to review.`, 'info');
    while (seen.size > 250) seen.delete(seen.values().next().value!);
    baseline = true;
  });
  onBeforeUnmount(() => bindAdminSounds(null));
}
