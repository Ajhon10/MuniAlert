<script setup lang="ts">
import AppForm from './AppForm.vue';
import FormInput from './FormInput.vue';
import PasswordInput from './PasswordInput.vue';
import { ref } from 'vue';
import { api } from '../services/api';
import { recoveryLink } from '../services/accountSecurity';
import { notify, useFeedback } from '../services/feedback';
const emit = defineEmits<{ done: [] }>();
const email = ref(''); const password = ref(''); const confirmation = ref(''); const busy = ref(false); const error = ref(''); const message = ref('');
const method = ref<'email' | 'code'>('email'); const code = ref('');
useFeedback([error]);
async function submit() {
  busy.value = true; error.value = ''; message.value = '';
  try {
    const result = await api<{ message: string }>(recoveryLink.value ? '/api/auth/recovery/consume' : method.value === 'code' ? '/api/auth/recovery/code' : '/api/auth/recovery', { method: 'POST', body: JSON.stringify(recoveryLink.value ? { ...recoveryLink.value, password: password.value || null, password_confirmation: confirmation.value || null } : method.value === 'code' ? { email: email.value, code: code.value.trim(), password: password.value, password_confirmation: confirmation.value } : { email: email.value }) });
    message.value = result.message; notify(result.message, 'info');
    if (recoveryLink.value || method.value === 'code') { recoveryLink.value = null; password.value = confirmation.value = code.value = ''; emit('done'); }
  } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; }
}
</script>
<template><AppForm class="form-body security-form" @submit.prevent="submit"><h3>{{ recoveryLink?.purpose === 'verify' ? 'Verify your email' : recoveryLink ? 'Choose a new password' : 'Recover your account' }}</h3><p>{{ recoveryLink ? 'Confirm this request to continue. Links expire after 30 minutes and work once.' : 'Choose an email reset link or a saved one-time recovery code. Email needs a verified deliverable address and configured mail. Codes must have been saved from Settings beforehand.' }}</p><div v-if="!recoveryLink" class="button-row" role="group" aria-label="Recovery method"><button type="button" class="button secondary" :aria-pressed="method === 'email'" @click="method = 'email'; error = ''">Email reset link</button><button type="button" class="button secondary" :aria-pressed="method === 'code'" @click="method = 'code'; error = ''">Use recovery code</button></div><label v-if="!recoveryLink">Email address<FormInput v-model="email" type="email" autocomplete="email" required name="email" /></label><template v-if="recoveryLink?.purpose === 'reset' || (!recoveryLink && method === 'code')"><label v-if="!recoveryLink">Saved recovery code<FormInput v-model="code" name="code" autocomplete="off" autocapitalize="off" spellcheck="false" /></label><PasswordInput label="New password" v-model="password" autocomplete="new-password" required minlength="12" maxlength="128" /><PasswordInput label="Confirm new password" v-model="confirmation" autocomplete="new-password" required minlength="12" maxlength="128" /></template><p v-if="message" role="status">{{ message }}</p><button class="button green full" :disabled="busy">{{ busy ? 'Please wait…' : recoveryLink?.purpose === 'verify' ? 'Verify email' : recoveryLink ? 'Change password' : method === 'code' ? 'Reset password with code' : 'Send email reset link' }}</button><button type="button" class="text-button" @click="recoveryLink = null; $emit('done')">Back to sign in</button></AppForm></template>
