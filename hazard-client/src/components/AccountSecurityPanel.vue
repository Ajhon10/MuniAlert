<script setup lang="ts">
import FormInput from './FormInput.vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { api } from '../services/api';
import type { User } from '../types';
const props = defineProps<{ user: User }>();
import { recoveryCodes } from '../services/accountSecurity';
import { notify, useFeedback } from '../services/feedback';
type Security = { mfa_enabled: boolean; mfa_required: boolean; recovery_codes_remaining: number; email_verified: boolean; recovery_available: boolean; email_eligible: boolean; email_report_updates: boolean };
const state = ref<Security>(); const error = ref(''); const busy = ref(false);
let active = true;
onBeforeUnmount(() => { active = false; });
useFeedback([error]);
async function load() { try { state.value = await api<Security>('/api/security/status'); } catch (e) { error.value = (e as Error).message; } }
onMounted(load);
async function regenerate() { busy.value = true; error.value = ''; try { const result = await api<{ recovery_codes: string[] }>('/api/security/recovery-codes', { method: 'POST' }); if (active) recoveryCodes.value = result.recovery_codes; await load(); } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; } }
async function verifyEmail() { busy.value = true; error.value = ''; try { const result = await api<{ message: string }>('/api/security/verify-email', { method: 'POST' }); notify(result.message, 'info'); } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; } }
async function emailUpdates(event: Event) {
  if (!state.value) return;
  busy.value = true; error.value = '';
  try {
    const value = (event.target as HTMLInputElement).checked;
    await api('/api/preferences/email', { method: 'PUT', body: JSON.stringify({ account_id: props.user.id, email_report_updates: value }) });
    state.value.email_report_updates = value; notify(value ? 'Report update emails enabled.' : 'Report update emails turned off.');
  } catch (e) { error.value = (e as Error).message; (event.target as HTMLInputElement).checked = state.value.email_report_updates; } finally { busy.value = false; }
}
</script>
<template>
  <section class="cms-card account-security"><h2>Account security & email</h2>
    <div v-if="state" class="security-form">
      <p>{{ state.mfa_enabled ? 'Two-factor authentication (2FA) is enabled. Use the six-digit code from your authenticator app.' : state.mfa_required ? 'Administrator sign-in requires authenticator-app 2FA.' : 'Use your password or connected provider to sign in.' }}</p>
      <h3>Saved recovery codes</h3><p>{{ state.recovery_codes_remaining }} unused recovery codes remaining. Save codes before you lose access: each can reset a forgotten password, and also replace an authenticator code when 2FA is enabled.</p>
      <button v-if="user.has_password !== false || state.mfa_enabled" class="button secondary" :disabled="busy" @click="regenerate">{{ state.recovery_codes_remaining ? 'Replace recovery codes' : 'Create recovery codes' }}</button>
      <h3>Email verification & recovery</h3><p>Your account email is {{ state.email_verified ? 'verified' : 'not verified' }}.</p>
      <button v-if="!state.email_verified && state.recovery_available && state.email_eligible" class="button secondary" :disabled="busy" @click="verifyEmail">Verify account email</button>
      <p v-if="!state.email_eligible" class="field-help">This is a sample address. Set a deliverable account email in My profile to receive verification and report emails.</p>
      <p v-if="!state.recovery_available" class="field-help">Email delivery is waiting for the municipality's mail configuration. Contact municipal support for account recovery.</p>
      <label class="reading-option"><span><strong>Report update emails</strong><small>Receive a reference and status when your own report is updated. Original evidence and private notes are not emailed.</small></span><FormInput type="checkbox" role="switch" aria-label="Report update emails" :checked="state.email_report_updates" :disabled="busy || (!state.email_report_updates && (!state.email_verified || !state.recovery_available || !state.email_eligible))" @change="emailUpdates" /></label>
      <p v-if="!state.email_verified" class="field-help">Verify your account email before enabling report update emails.</p>
    </div><p v-else-if="!error" role="status">Loading security settings…</p>
  </section>
</template>
