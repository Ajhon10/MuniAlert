<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { api } from '../services/api';
import { cancelSocialAuth, socialBusy, socialError, startSocialAuth, type SocialProvider } from '../services/socialAuth';
const props = withDefaults(defineProps<{ intent?: string; mode?: 'login' | 'link'; currentPassword?: string; connected?: string[]; disabled?: boolean }>(), { intent: 'overview', mode: 'login', connected: () => [] });
const providers = ref<Record<SocialProvider, { enabled: boolean }>>({ google: { enabled: false }, facebook: { enabled: false } });
const loading = ref(true);
onMounted(async () => { try { providers.value = await api<typeof providers.value>('/api/auth/providers'); } catch {} finally { loading.value = false; } });
function start(provider: SocialProvider) { void startSocialAuth(provider, { intent: props.intent, mode: props.mode, currentPassword: props.currentPassword }); }
</script>
<template>
  <div class="social-auth" :aria-busy="socialBusy || loading">
    <div class="social-auth-buttons">
      <button v-for="provider in (['google', 'facebook'] as const)" :key="provider" type="button" class="button social-button" :disabled="disabled || socialBusy || loading || !providers[provider]?.enabled || connected.includes(provider) || (mode === 'link' && !currentPassword)" @click="start(provider)">
        <svg v-if="provider === 'google'" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.39-.18-2.05H12v3.88h5.38a4.6 4.6 0 0 1-2 3.02v2.51h3.25c1.9-1.75 2.97-4.33 2.97-7.36Z"/><path fill="#34A853" d="M12 22c2.7 0 4.96-.9 6.61-2.41l-3.25-2.51c-.9.6-2.04.96-3.36.96-2.61 0-4.83-1.77-5.62-4.15H3.02v2.59A10 10 0 0 0 12 22Z"/><path fill="#FBBC05" d="M6.38 13.89A6 6 0 0 1 6.06 12c0-.66.11-1.3.32-1.89V7.52H3.02A10 10 0 0 0 2 12c0 1.61.39 3.14 1.02 4.48Z"/><path fill="#EA4335" d="M12 5.96c1.47 0 2.79.5 3.83 1.5l2.88-2.88A9.6 9.6 0 0 0 12 2a10 10 0 0 0-8.98 5.52l3.36 2.59A5.97 5.97 0 0 1 12 5.96Z"/></svg>
        <svg v-else aria-hidden="true" width="20" height="20" viewBox="0 0 24 24"><path fill="#1877F2" d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.69.24 2.69.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07Z"/></svg>
        {{ connected.includes(provider) ? 'Connected to' : mode === 'link' ? 'Connect' : 'Continue with' }} {{ provider === 'google' ? 'Google' : 'Facebook' }}
      </button>
    </div>
    <p v-if="loading" class="field-help" role="status">Checking sign-in options…</p>
    <p v-else-if="!providers.google?.enabled && !providers.facebook?.enabled" class="field-help">Google and Facebook sign-in aren’t available right now. You can continue with email.</p>
    <p v-if="socialBusy" class="field-help" role="status">Complete sign-in in your browser. <button type="button" class="text-button" @click="cancelSocialAuth().catch(() => {})">Cancel sign-in</button></p>
    <p v-if="socialError" class="form-error" role="alert">{{ socialError }}</p>
  </div>
</template>
<style scoped>
.social-auth-buttons { display: grid; gap: .65rem; }
.social-button { width: 100%; min-height: 48px; justify-content: center; background: rgba(255,255,255,.82); color: #23352d; border: 1px solid rgba(44,71,54,.2); box-shadow: inset 0 1px 0 #fff; font-weight: 600; }
.social-button:focus-visible { outline: 3px solid #267349; outline-offset: 3px; }
.social-button:disabled { opacity: .6; cursor: default; }
.social-button svg { flex-shrink: 0; }
</style>
