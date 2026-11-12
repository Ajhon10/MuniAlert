<script setup lang="ts">
import { ALargeSmall, BookOpen, RefreshCw } from '@lucide/vue';
import { readingAccount, retryReadingSave } from '../services/readingAccount';
import AccountSecurityPanel from './AccountSecurityPanel.vue';
import AdminAlertSettings from './AdminAlertSettings.vue';
import type { Category, User } from '../types';
defineProps<{ user: User | null; categories?: Category[] }>();
defineEmits<{ reading: []; guide: []; signIn: [] }>();
</script>
<template>
  <div class="settings-page">
    <section class="cms-card settings-card"><div><ALargeSmall :size="26" aria-hidden="true" /><h2>Reading & accessibility</h2></div><p>Choose text size, bold text, contrast, spacing, motion and message visibility.</p><p class="field-help" role="status">{{ !readingAccount.signedIn ? 'Guest preferences are saved on this device. Sign in to save your own account settings.' : readingAccount.saving ? 'Saving your account preferences…' : readingAccount.error || 'Your reading preferences are saved separately for your account and restored after sign-in.' }}</p><button class="button primary" @click="$emit('reading')">Reading and accessibility settings</button><button v-if="readingAccount.error" class="button secondary" @click="retryReadingSave"><RefreshCw :size="18" aria-hidden="true" />Retry saving preferences</button></section>
    <section class="cms-card settings-card"><div><BookOpen :size="26" aria-hidden="true" /><h2>Help & user guide</h2></div><p>Read the citizen or administrator manual, search for a feature or start the guided tour.</p><button class="button secondary" @click="$emit('guide')">Open guide and user manual</button></section>
    <AdminAlertSettings v-if="user?.role === 'admin'" :categories="categories || []" />
    <AccountSecurityPanel v-if="user" :key="user.id" :user="user" />
    <section v-else class="cms-card settings-card"><h2>Account security & email</h2><p>Sign in to manage your account's security and email preferences.</p><button class="button secondary" @click="$emit('signIn')">Sign in to manage settings</button></section>
  </div>
</template>
<style scoped>
.settings-page { display: grid; gap: 24px; max-width: 980px; }
.settings-card { display: flex; flex-direction: column; align-items: flex-start; gap: 16px; padding: 24px; min-width: 0; }
.settings-card > div { display: flex; gap: 12px; align-items: center; }
.settings-card h2 { font-size: 1.25rem; }
.settings-card p { line-height: 1.7; overflow-wrap: anywhere; }
.settings-card .button { white-space: normal; text-align: left; }
</style>
