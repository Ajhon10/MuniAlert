<script setup lang="ts">
import { Bell, ChevronRight, Settings } from '@lucide/vue';
import identity from '../config/identity.json';
defineProps<{ section?: string; time: string; online: boolean; demo: boolean; admin: boolean; unread: number; initial: string }>();
defineEmits<{ settings: []; notifications: []; account: [] }>();
</script>

<template>
  <header class="topbar">
    <div class="breadcrumb"><span>{{ identity.name }}</span><ChevronRight :size="14" /><strong>{{ section }}</strong></div>
    <div class="top-actions">
      <button type="button" class="icon-button" aria-label="Open settings" @click="$emit('settings')"><Settings :size="21" aria-hidden="true" /></button>
      <time class="ph-clock" aria-label="Philippine time">{{ time }}</time>
      <span class="connection" :class="{ demo, disconnected: !online && !demo }" :aria-label="demo ? 'Demo workspace' : online ? 'Connected to municipality' : 'Connecting to municipality'" :title="demo ? 'Demo workspace' : online ? 'Connected' : 'Connecting to municipality'"><i></i>{{ demo ? 'Demo workspace' : online ? 'Connected' : 'Connecting to municipality' }}</span>
      <button type="button" class="icon-button notification-button" aria-label="Open notifications" @click="$emit('notifications')"><Bell :size="20" /><i v-if="unread"></i></button>
      <button type="button" class="avatar top-avatar" aria-label="Account" @click="$emit('account')">{{ initial }}</button>
    </div>
  </header>
</template>
