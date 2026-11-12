<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { X } from '@lucide/vue';
import identity from '../config/identity.json';
import { lockModalScroll } from '../services/modalScroll';
import { reportInvalid } from '../services/feedback';
defineProps<{ title: string; wide?: boolean }>(); const emit = defineEmits<{ close: [] }>(); const dialog = ref<HTMLDialogElement>();
let releaseScroll: (() => void) | undefined;
onMounted(() => { dialog.value?.showModal(); releaseScroll = lockModalScroll(); });
onBeforeUnmount(() => { dialog.value?.close(); releaseScroll?.(); });
function backdrop(event: MouseEvent) { if (event.target !== dialog.value) return; const box = dialog.value.getBoundingClientRect(); if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) emit('close'); }
</script>
<template>
  <dialog ref="dialog" class="modal" :class="{ wide }" :aria-label="title" @cancel.prevent="emit('close')" @click="backdrop" @invalid.capture="reportInvalid">
    <header class="modal-header"><div><span class="eyebrow">{{ identity.name }} · MUNICIPAL REPORTING</span><h2>{{ title }}</h2></div><button type="button" class="icon-button" aria-label="Close dialog" @click="emit('close')"><X :size="21" /></button></header>
    <div class="modal-body" role="region" :aria-label="`${title} content`" tabindex="0"><slot /></div>
    <footer class="modal-footer"><slot name="footer"><button type="button" class="button secondary" @click="emit('close')">Close</button></slot></footer>
  </dialog>
</template>
