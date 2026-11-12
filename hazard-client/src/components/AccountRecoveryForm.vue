<script setup lang="ts">
import PasswordInput from './PasswordInput.vue';
import { ref } from 'vue';
import { api } from '../services/api';
import { recoveryLink } from '../services/accountSecurity';
import { notify, useFeedback } from '../services/feedback';
const emit = defineEmits<{ done: [] }>();
const email = ref(''); const password = ref(''); const confirmation = ref(''); const busy = ref(false); const error = ref(''); const message = ref('');
useFeedback([error]);
async function submit() {
  busy.value = true; error.value = ''; message.value = '';
  try {
    const result = await api<{ message: string }>(recoveryLink.value ? '/api/auth/recovery/consume' : '/api/auth/recovery', { method: 'POST', body: JSON.stringify(recoveryLink.value ? { ...recoveryLink.value, password: password.value || null, password_confirmation: confirmation.value || null } : { email: email.value }) });
    message.value = result.message; notify(result.message, 'info');
    if (recoveryLink.value) { recoveryLink.value = null; password.value = confirmation.value = ''; emit('done'); }
  } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; }
}
</script>
<template><form class="form-body security-form" @submit.prevent="submit"><h3>{{ recoveryLink?.purpose === 'verify' ? 'Verify your email' : recoveryLink ? 'Choose a new password' : 'Recover your account' }}</h3><p>{{ recoveryLink ? 'Confirm this request to continue. Links expire after 30 minutes and work once.' : 'Recovery is available only for a previously verified local email and when municipal email delivery is enabled. Otherwise, contact municipal support.' }}</p><label v-if="!recoveryLink">Email address<input v-model="email" type="email" autocomplete="email" required></label><template v-else-if="recoveryLink.purpose === 'reset'"><PasswordInput label="New password" v-model="password" autocomplete="new-password" required minlength="12" maxlength="128" /><PasswordInput label="Confirm new password" v-model="confirmation" autocomplete="new-password" required minlength="12" maxlength="128" /></template><p v-if="message" role="status">{{ message }}</p><button class="button green full" :disabled="busy">{{ busy ? 'Please wait…' : recoveryLink?.purpose === 'verify' ? 'Verify email' : recoveryLink ? 'Change password' : 'Request recovery link' }}</button><button type="button" class="text-button" @click="recoveryLink = null; $emit('done')">Back to sign in</button></form></template>
