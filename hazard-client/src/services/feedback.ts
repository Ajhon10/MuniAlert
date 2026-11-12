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

let invalidBatch = false;
let nextFieldId = 0;
export function reportInvalid(event: Event) {
  event.preventDefault();
  if (invalidBatch) return;
  invalidBatch = true; setTimeout(() => { invalidBatch = false; }, 0);
  const input = event.target as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
  const label = input.getAttribute('aria-label') || input.labels?.[0]?.textContent?.trim() || 'the highlighted field';
  input.id ||= `validation-field-${++nextFieldId}`;
  const id = notify(`Check ${label}. ${input.validationMessage}`, 'error', input.id);
  if (id != null) {
    input.setAttribute('aria-invalid', 'true'); input.dataset.toastInvalid = String(id);
    const descriptions = new Set((input.getAttribute('aria-describedby') || '').split(' ').filter(Boolean));
    descriptions.add(`toast-message-${id}`); input.setAttribute('aria-describedby', [...descriptions].join(' '));
    const corrected = () => { if (input.validity.valid) { dismissToast(id); input.removeEventListener('input', corrected); input.removeEventListener('change', corrected); } };
    input.addEventListener('input', corrected); input.addEventListener('change', corrected);
  }
  queueMicrotask(() => input.focus());
}

/** One persistent error toast per source; no duplicate inline validation. */
export function useFeedback(errors: Ref<string>[], successes: Ref<string>[] = []) {
  for (const source of errors) {
    let id: number | undefined;
    watch(source, message => { if (id != null) dismissToast(id); id = message ? notify(message, 'error') : undefined; }, { flush: 'sync' });
    onScopeDispose(() => { if (id != null) dismissToast(id); });
  }
  for (const source of successes) watch(source, message => { if (message) notify(message); }, { flush: 'sync' });
}
