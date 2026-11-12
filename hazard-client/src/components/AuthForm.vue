<script setup lang="ts">
import AppForm from './AppForm.vue';
import FormInput from './FormInput.vue';
import PasswordInput from './PasswordInput.vue';
import { useFeedback } from '../services/feedback';
import { nextTick, onBeforeUnmount, ref, useId } from 'vue'; import { ArrowRight, ShieldCheck } from '@lucide/vue';
import Modal from './Modal.vue';
import MfaChallengeForm from './MfaChallengeForm.vue';
import AccountRecoveryForm from './AccountRecoveryForm.vue';
import { pendingMfa, recoveryLink } from '../services/accountSecurity';
const recovering = ref(false);
let active = true;
onBeforeUnmount(() => { active = false; });
import { authenticate } from '../services/api'; import type { User } from '../types';
import { socialBusy } from '../services/socialAuth';
withDefaults(defineProps<{ socialIntent?: string }>(), { socialIntent: 'overview' });
const emit = defineEmits<{ success: [user: User]; close: [] }>(); const formId = useId(); const register = ref(false); const name = ref(''); const email = ref(''); const password = ref(''); const busy = ref(false); const error = ref('');
useFeedback([error], []);
async function submit() { if (busy.value || socialBusy.value) return; busy.value = true; error.value = ''; try { const result = await authenticate(register.value, { email: email.value, password: password.value, ...(register.value ? { name: name.value } : {}) }); if ('mfa_required' in result) { if (!active) return; pendingMfa.value = result; password.value = ''; } else emit('success', result); } catch (e) { error.value = (e as Error).message; await nextTick(); const form = document.getElementById(formId); (form?.querySelector<HTMLInputElement>('[aria-invalid=true]') || form?.querySelector<HTMLInputElement>('input[type=email]'))?.focus(); } finally { busy.value = false; } }
</script>
<template><Modal title="Welcome to your community" @close="emit('close')"><MfaChallengeForm v-if="pendingMfa" @success="emit('success', $event)" @restart="error = ''" /><AccountRecoveryForm v-else-if="recovering || recoveryLink" @done="recovering = false; register = false" /><AppForm v-else :id="formId" class="form-body" @submit.prevent="submit"><div class="notice neutral"><ShieldCheck :size="24" /><p>One account to report hazards and follow your municipality’s response.</p></div><p class="field-help">You stay signed in on this device until you sign out or your session expires after extended inactivity.</p><label v-if="register">Full name<FormInput v-model="name" autocomplete="name" required minlength="2" maxlength="80" placeholder="Your full name" name="name" /></label><label>Email address<FormInput v-model="email" type="email" autocomplete="email" required placeholder="you@example.com" name="email" /></label><PasswordInput :key="register ? 'registration' : 'login'" label="Password" v-model="password" :autocomplete="register ? 'new-password' : 'current-password'" required minlength="12" maxlength="128" placeholder="At least 12 characters" /></AppForm><template #footer><template v-if="!pendingMfa && !recovering && !recoveryLink"><button type="submit" :form="formId" class="button primary full" :disabled="busy || socialBusy">{{ busy ? 'Please wait…' : register ? 'Create your account' : 'Sign in' }}<ArrowRight :size="17" /></button><p class="form-foot">{{ register ? 'Already have an account?' : 'New to the community?' }} <button type="button" class="text-button" @click="register = !register; password = ''; error = ''">{{ register ? 'Sign in' : 'Create an account' }}</button></p><button v-if="!register" type="button" class="text-button" @click="recovering = true">Forgot your password?</button></template></template></Modal></template>
