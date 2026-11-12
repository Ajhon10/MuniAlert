import { ref } from 'vue';
export type MfaChallenge = { mfa_required: true; enrollment_required: boolean; challenge_token: string; expires_in: number; secret?: string; otpauth_uri?: string };
export const pendingMfa = ref<MfaChallenge | null>(null);
export const mfaIntent = ref('overview');
export const recoveryCodes = ref<string[]>([]);
export type StepUp = { password_required: boolean; mfa_required: boolean };
export const stepUp = ref<StepUp | null>(null);
let complete: (() => void) | undefined; let cancel: ((reason: Error) => void) | undefined;
let waiting: Promise<void> | undefined;
export function requestStepUp(options: StepUp): Promise<void> {
  if (waiting) return waiting;
  stepUp.value = options;
  waiting = new Promise<void>((resolve, reject) => { complete = resolve; cancel = reject; }).finally(() => { waiting = undefined; });
  return waiting;
}
export function finishStepUp() { stepUp.value = null; complete?.(); complete = cancel = undefined; }
export function cancelStepUp() { stepUp.value = null; cancel?.(new Error('Identity verification was cancelled. Your changes were not saved.')); complete = cancel = undefined; }
export function clearAccountSecurity() { pendingMfa.value = null; recoveryCodes.value = []; recoveryLink.value = null; if (stepUp.value) cancelStepUp(); }
export const recoveryLink = ref<{ purpose: 'reset' | 'verify'; token: string } | null>(null);
// Remove proofs before components make requests. They never enter local storage,
// browser query strings, analytics or the native session vault.
const fragment = new URLSearchParams(location.hash.slice(1));
for (const purpose of ['reset', 'verify'] as const) {
  const token = fragment.get(purpose);
  if (token && /^[a-f0-9-]{36}\.[A-Za-z0-9]{64}$/.test(token)) recoveryLink.value = { purpose, token };
}
if (fragment.has('reset') || fragment.has('verify')) history.replaceState(null, '', location.pathname + location.search);
