<script setup lang="ts">
import PasswordInput from './PasswordInput.vue';
import TwoFactorInput from './TwoFactorInput.vue';
import { onBeforeUnmount, ref } from 'vue';
import Modal from './Modal.vue';
import { api } from '../services/api';
import { stepUp, finishStepUp, cancelStepUp } from '../services/accountSecurity';
import { notify, useFeedback } from '../services/feedback';
const password = ref(''); const code = ref(''); const busy = ref(false); const error = ref('');
let active = true;
onBeforeUnmount(() => { active = false; password.value = code.value = ''; });
useFeedback([error]);
async function submit() {
  if (busy.value) return;
  busy.value = true; error.value = '';
  try {
    await api('/api/security/reauthenticate', { method: 'POST', body: JSON.stringify({ password: password.value || null, code: code.value || null }) });
    if (!active) return;
    password.value = code.value = ''; notify('Identity verified. Continuing your action.'); finishStepUp();
  } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; }
}
</script>
<template><Modal title="Verify your identity" @close="cancelStepUp"><form class="form-body security-form" @submit.prevent="submit"><p>For your security, confirm your identity before this change. Verification lasts five minutes on this device.</p><PasswordInput v-if="stepUp?.password_required" label="Current password" v-model="password" autocomplete="current-password" required /><TwoFactorInput v-if="stepUp?.mfa_required" v-model="code" /><button class="button green full" :disabled="busy">{{ busy ? 'Verifying…' : 'Verify identity' }}</button></form></Modal></template>
