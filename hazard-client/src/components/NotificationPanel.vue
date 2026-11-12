<script setup lang="ts">
import { Bell, CheckCheck, ChevronRight, TriangleAlert } from '@lucide/vue';
import Modal from './Modal.vue';
import { relativeTime, type Notice } from '../types';
import { onMounted, onUnmounted, ref } from 'vue';

const inbox = ref<HTMLElement>(); const rowHeight = ref('112px'); let observer: ResizeObserver | undefined;
onMounted(() => {
  const header = inbox.value?.closest('dialog')?.querySelector('.modal-header');
  if (header) { observer = new ResizeObserver(() => { rowHeight.value = `${header.getBoundingClientRect().height}px`; }); observer.observe(header); }
});
onUnmounted(() => observer?.disconnect());
defineProps<{ notices: Notice[]; unread: number; admin: boolean; signedIn: boolean; pushBusy: boolean; busy: boolean; demo: boolean }>();
defineEmits<{ close: []; open: [notice: Notice]; readAll: []; push: []; signIn: [] }>();
</script>
<template>
  <Modal title="Notifications" @close="$emit('close')"><div ref="inbox" class="form-body notification-inbox" :style="{ '--notification-height': rowHeight }">
    <p>{{ admin ? 'New hazards for municipal review. Select an alert to open the report queue and its details.' : 'Select an update to open the related report in My reports.' }}</p>
    <p v-if="busy" role="status">Opening the related report…</p>
    <p v-if="!signedIn">Sign in to see your private notifications.</p><button v-if="!signedIn" class="button primary" @click="$emit('signIn')">Sign in</button>
    <template v-else><div class="button-row"><span role="status">{{ unread }} unread</span><button type="button" class="button secondary" :disabled="!unread || busy || demo" @click="$emit('readAll')"><CheckCheck :size="18" aria-hidden="true" />Mark all read</button><button v-if="!admin" type="button" class="button secondary" :disabled="pushBusy || demo" @click="$emit('push')">{{ pushBusy ? 'Enabling…' : 'Enable push' }}</button></div>
      <div v-if="!notices.length" class="empty-state"><Bell :size="32" aria-hidden="true" /><h3>You’re all caught up</h3><p>New updates will appear here.</p></div>
      <button v-for="notice in notices" :key="notice.id" type="button" class="notification-row" :class="{ unread: !notice.read_at }" :disabled="busy" @click="$emit('open', notice)"><TriangleAlert v-if="notice.data.assistance_needed" :size="22" aria-hidden="true" /><Bell v-else :size="20" aria-hidden="true" /><span><span class="notification-meta"><strong>{{ notice.data.reference_number }}</strong><small>{{ relativeTime(notice.created_at) }}</small></span><p>{{ notice.data.message }}</p><small v-if="!notice.read_at" class="notification-unread">Unread</small></span><ChevronRight :size="18" aria-hidden="true" /></button>
    </template>
  </div></Modal>
</template>
<style scoped>
.notification-inbox .notification-row { width: 100%; height: var(--notification-height); min-height: var(--notification-height); padding-block: 8px; text-align: left; min-width: 0; align-items: center; }
.notification-row > span { flex: 1; min-width: 0; overflow: hidden; }
.notification-row p { line-height: 1.5; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 1; line-clamp: 1; overflow: hidden; overflow-wrap: anywhere; }
:global(.readingSpace) .notification-row p { line-height: 1.9; }
.notification-row strong { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.notification-row small { display: block; white-space: nowrap; }
.notification-meta { display: flex; gap: 8px; align-items: baseline; justify-content: space-between; }
.notification-meta strong { min-width: 0; }
.notification-meta small { flex-shrink: 0; }
.notification-unread { color: var(--green); font-weight: 700; }
.notification-row.unread { border-inline-start: 4px solid var(--primary, #17664f); }
</style>
