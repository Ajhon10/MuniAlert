<script setup lang="ts">
import { Bell, ChevronRight } from '@lucide/vue';
import AccountMenu from './AccountMenu.vue';
import identity from '../config/identity.json';
defineProps<{ section?: string; time: string; online: boolean; demo: boolean; admin: boolean; unread: number; initial: string; name?: string }>();
defineEmits<{ settings: []; notifications: []; profile: []; logout: []; account: [] }>();
</script>

<template>
  <header class="topbar">
    <div class="breadcrumb"><span>{{ identity.name }}</span><ChevronRight :size="14" /><strong>{{ section }}</strong></div>
    <div class="top-actions">
      <time class="ph-clock" aria-label="Philippine time">{{ time }}</time>
      <span class="connection" :class="{ demo, disconnected: !online && !demo }" :aria-label="demo ? 'Demo workspace' : online ? 'Connected to municipality' : 'Connecting to municipality'" :title="demo ? 'Demo workspace' : online ? 'Connected' : 'Connecting to municipality'"><i></i>{{ demo ? 'Demo workspace' : online ? 'Connected' : 'Connecting to municipality' }}</span>
      <button type="button" class="icon-button notification-button" aria-label="Open notifications" @click="$emit('notifications')"><Bell :size="20" /><i v-if="unread"></i></button>
      <AccountMenu :initial="initial" :name="name" :admin="admin" @settings="$emit('settings')" @profile="$emit('profile')" @logout="$emit('logout')" @sign-in="$emit('account')" />
    </div>
  </header>
</template>
