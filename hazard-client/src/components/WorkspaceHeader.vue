<script setup lang="ts">
import { Bell, ChevronRight, CircleHelp } from '@lucide/vue';
import identity from '../config/identity.json';
defineProps<{ section?: string; time: string; online: boolean; demo: boolean; admin: boolean; unread: number; initial: string }>();
defineEmits<{ guide: []; notifications: []; account: [] }>();
</script>

<template>
  <header class="topbar">
    <div class="breadcrumb"><span>{{ identity.name }}</span><ChevronRight :size="14" /><strong>{{ section }}</strong></div>
    <div class="top-actions">
      <button type="button" class="icon-button" aria-label="Open guide and user manual" @click="$emit('guide')"><CircleHelp :size="21" /></button>
      <time class="ph-clock" aria-label="Philippine time">{{ time }}</time>
      <span class="connection" :class="{ demo, disconnected: !online && !demo }" :aria-label="demo ? 'Demo workspace' : online ? 'Connected to municipality' : 'Connecting to municipality'" :title="demo ? 'Demo workspace' : online ? 'Connected' : 'Connecting to municipality'"><i></i>{{ demo ? 'Demo workspace' : online ? 'Connected' : 'Connecting to municipality' }}</span>
      <button v-if="!admin" type="button" class="icon-button notification-button" aria-label="Open notifications" @click="$emit('notifications')"><Bell :size="20" /><i v-if="unread"></i></button>
      <button type="button" class="avatar top-avatar" aria-label="Account" @click="$emit('account')">{{ initial }}</button>
    </div>
  </header>
</template>
