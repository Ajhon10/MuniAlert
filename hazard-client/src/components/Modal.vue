<script setup lang="ts">
import { useSlots, Comment, Fragment, Text, type VNode } from 'vue';
import { X } from '@lucide/vue';
import identity from '../config/identity.json';
import { useNativeDialog } from '../composables/useNativeDialog';
defineProps<{ title: string; wide?: boolean }>(); const emit = defineEmits<{ close: [] }>(); const dialog = useNativeDialog();
const slots = useSlots();
function meaningful(nodes: VNode[] = []): boolean {
  return nodes.some(node => node.type !== Comment && (node.type === Fragment || Array.isArray(node.children) ? meaningful(node.children as VNode[]) : node.type !== Text || String(node.children || '').trim().length > 0));
}
function hasFooter() { return meaningful(slots.footer?.()); }
function backdrop(event: MouseEvent) { if (event.target !== dialog.value) return; const box = dialog.value.getBoundingClientRect(); if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) emit('close'); }
</script>
<template>
  <dialog ref="dialog" class="modal" :class="{ wide }" :aria-label="title" @cancel.prevent="emit('close')" @click="backdrop">
    <header class="modal-header"><div><span class="eyebrow">{{ identity.name }} · MUNICIPAL REPORTING</span><h2>{{ title }}</h2></div><button type="button" class="icon-button" aria-label="Close dialog" @click="emit('close')"><X :size="21" /></button></header>
    <div class="modal-body" role="region" :aria-label="`${title} content`" tabindex="0"><slot /></div>
    <footer v-if="hasFooter()" class="modal-footer"><slot name="footer" /></footer>
  </dialog>
</template>
