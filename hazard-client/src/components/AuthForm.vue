<script setup lang="ts">
import { ref } from 'vue'; import { ArrowRight, ShieldCheck } from '@lucide/vue';
import { authenticate } from '../services/api'; import type { User } from '../types';
import SocialLoginButtons from './SocialLoginButtons.vue';
import { socialBusy } from '../services/socialAuth';
withDefaults(defineProps<{ socialIntent?: string }>(), { socialIntent: 'overview' });
const emit = defineEmits<{ success: [user: User] }>(); const register = ref(false); const name = ref(''); const email = ref(''); const password = ref(''); const busy = ref(false); const error = ref('');
async function submit() { if (busy.value || socialBusy.value) return; busy.value = true; error.value = ''; try { emit('success', await authenticate(register.value, { email: email.value, password: password.value, ...(register.value ? { name: name.value } : {}) })); } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; } }
</script>
<template><form class="form-body" @submit.prevent="submit"><div class="notice neutral"><ShieldCheck :size="24" /><p>One account to report hazards and follow your municipality’s response.</p></div><p class="field-help">You stay signed in on this device until you sign out or your session expires after extended inactivity.</p><SocialLoginButtons :intent="socialIntent" :disabled="busy" /><p class="auth-divider">or use email</p><label v-if="register">Full name<input v-model="name" autocomplete="name" required minlength="2" maxlength="80" placeholder="Your full name"></label><label>Email address<input v-model="email" type="email" autocomplete="email" required placeholder="you@example.com"></label><label>Password<input v-model="password" type="password" :autocomplete="register ? 'new-password' : 'current-password'" required minlength="12" maxlength="128" placeholder="At least 12 characters"></label><p v-if="error" class="form-error" role="alert">{{ error }}</p><button class="button primary full" :disabled="busy || socialBusy">{{ busy ? 'Please wait…' : register ? 'Create your account' : 'Sign in' }}<ArrowRight :size="17" /></button><p class="form-foot">{{ register ? 'Already have an account?' : 'New to the community?' }} <button type="button" class="text-button" @click="register = !register; error = ''">{{ register ? 'Sign in' : 'Create an account' }}</button></p></form></template>

<style scoped>
.auth-divider { display: flex; align-items: center; gap: .8rem; color: #52685b; font-size: .82rem; }
.auth-divider::before, .auth-divider::after { content: ""; height: 1px; flex: 1; background: rgba(50,80,60,.18); }
</style>
