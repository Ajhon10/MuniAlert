<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { X } from '@lucide/vue';
import identity from '../config/identity.json';
defineProps<{ title: string; wide?: boolean }>(); const emit = defineEmits<{ close: [] }>(); const dialog = ref<HTMLDialogElement>(); let prior = '';
onMounted(() => { dialog.value?.showModal(); prior = document.body.style.overflow; document.body.style.overflow = 'hidden'; });
onBeforeUnmount(() => { dialog.value?.close(); document.body.style.overflow = prior; });
function backdrop(event: MouseEvent) { if (event.target !== dialog.value) return; const box = dialog.value.getBoundingClientRect(); if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) emit('close'); }
</script>
<template><dialog ref="dialog" class="modal" :class="{ wide }" :aria-label="title" @cancel.prevent="emit('close')" @click="backdrop"><header class="modal-header"><div><span class="eyebrow">{{ identity.name }} · MUNICIPAL REPORTING</span><h2>{{ title }}</h2></div><button class="icon-button" aria-label="Close dialog" @click="emit('close')"><X :size="21" /></button></header><slot /></dialog></template>
