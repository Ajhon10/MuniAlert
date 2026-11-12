import { onScopeDispose, readonly, ref, watch, type Ref } from 'vue';

export type ToastTone = 'success' | 'error' | 'info';
export type Toast = { id: number; message: string; tone: ToastTone; fieldId?: string };
const items = ref<Toast[]>([]);
let nextId = 0;
export const toasts = readonly(items);
export function notify(message: string, tone: ToastTone = 'success', fieldId?: string) {
  if (!message.trim()) return;
  const existing = items.value.find(item => item.message === message && item.tone === tone && item.fieldId === fieldId);
  if (existing && tone === 'error') return existing.id;
  if (existing) dismissToast(existing.id);
  const id = ++nextId;
  items.value.push({ id, message, tone, fieldId });
  // Keep each message available for dismissal without covering the whole phone.
  if (items.value.length > 4) dismissToast(items.value[0]!.id);
  return id;
}
export function dismissToast(id: number) {
  const item = items.value.find(item => item.id === id);
  const field = item?.fieldId ? document.getElementById(item.fieldId) : null;
  if (field) {
    const descriptions = (field.getAttribute('aria-describedby') || '').split(' ').filter(value => value && value !== `toast-message-${id}`);
    if (descriptions.length) field.setAttribute('aria-describedby', descriptions.join(' ')); else field.removeAttribute('aria-describedby');
    if (field.dataset.toastInvalid === String(id)) { field.removeAttribute('aria-invalid'); delete field.dataset.toastInvalid; }
  }
  items.value = items.value.filter(item => item.id !== id);
}

let nextFieldId = 0;
let serverValidation: { message: string; field: HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement | null } | null = null;
export function prepareServerValidation(message: string, errors: Record<string, string[]>) {
  const root = [...document.querySelectorAll('dialog[open]')].at(-1) || document;
  const names = Object.keys(errors);
  const field = [...root.querySelectorAll<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>('input, textarea, select')].find(input => names.includes(input.name));
  serverValidation = { message, field: field || null };
}
function serverError(message: string) {
  const validation = serverValidation?.message === message ? serverValidation : null; serverValidation = null;
  const field = validation?.field;
  if (!field) return notify(message, 'error');
  field.id ||= `validation-field-${++nextFieldId}`;
  const id = notify(message, 'error', field.id);
  if (id != null) { field.setAttribute('aria-invalid', 'true'); field.dataset.toastInvalid = String(id); field.setAttribute('aria-describedby', `${field.getAttribute('aria-describedby') || ''} toast-message-${id}`.trim()); field.addEventListener('input', () => dismissToast(id), { once: true }); }
  queueMicrotask(() => field.focus());
  return id;
}
/** One persistent error toast per source; no duplicate inline validation. */
export function useFeedback(errors: Ref<string>[], successes: Ref<string>[] = []) {
  for (const source of errors) {
    let id: number | undefined;
    watch(source, message => { if (id != null) dismissToast(id); id = message ? serverError(message) : undefined; }, { flush: 'sync' });
    onScopeDispose(() => { if (id != null) dismissToast(id); });
  }
  for (const source of successes) watch(source, message => { if (message) notify(message); }, { flush: 'sync' });
}
