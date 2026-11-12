<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import QRCode from 'qrcode';
import TwoFactorInput from './TwoFactorInput.vue';
import { api, acceptSession, native, revokeIssuedToken } from '../services/api';
import { pendingMfa, recoveryCodes } from '../services/accountSecurity';
import { useFeedback } from '../services/feedback';
import type { User } from '../types';
const emit = defineEmits<{ success: [user: User]; restart: [] }>();
const code = ref(''); const busy = ref(false); const error = ref(''); const qr = ref('');
let active = true;
onBeforeUnmount(() => { active = false; code.value = qr.value = ''; });
useFeedback([error]);
onMounted(async () => {
  if (pendingMfa.value?.otpauth_uri) {
    try { qr.value = await QRCode.toDataURL(pendingMfa.value.otpauth_uri, { width: 240, margin: 2 }); }
    catch { /* Manual key entry remains available without QR rendering. */ }
  }
});
async function verify() {
  if (!pendingMfa.value || busy.value) return;
  busy.value = true; error.value = '';
  try {
    const result = await api<{ user: User; token?: string; recovery_codes?: string[] }>(`${native ? '/api' : ''}/auth/mfa/verify`, { method: 'POST', body: JSON.stringify({ challenge_token: pendingMfa.value.challenge_token, code: code.value.trim() }) });
    if (!active) { if (result.token) await revokeIssuedToken(result.token); else await api('/api/auth/logout', { method: 'POST' }); return; }
    const user = await acceptSession(result);
    recoveryCodes.value = result.recovery_codes || [];
    pendingMfa.value = null; code.value = '';
    emit('success', user);
  } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; }
}
</script>
<template>
  <form class="form-body security-form" @submit.prevent="verify">
    <h3>{{ pendingMfa?.enrollment_required ? 'Set up two-factor authentication (2FA)' : 'Verify your sign-in with 2FA' }}</h3>
    <template v-if="pendingMfa?.enrollment_required">
      <p>Add MuniAlert to an authenticator app, such as your existing phone authenticator. Scan this QR code or enter the setup key manually.</p>
      <img v-if="qr" class="mfa-qr" :src="qr" alt="Scan this private setup QR code with your authenticator app" width="240" height="240">
      <label>Private setup key<input :value="pendingMfa.secret" readonly autocomplete="off" spellcheck="false"></label>
      <p class="field-help">Keep this key private. Setup expires after five minutes. Your account becomes accessible only after a correct code.</p>
    </template>
    <p v-else>Enter a fresh code from your authenticator app, or one of your saved recovery codes. Each code can be used once.</p>
    <TwoFactorInput v-model="code" :allow-recovery="!pendingMfa?.enrollment_required" />
    <button class="button green full" :disabled="busy">{{ busy ? 'Verifying…' : 'Verify and sign in' }}</button>
    <button type="button" class="text-button" :disabled="busy" @click="pendingMfa = null; $emit('restart')">Start sign-in again</button>
  </form>
</template>
