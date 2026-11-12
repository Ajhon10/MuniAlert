<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { dismissToast, toasts } from '../services/feedback';
import ToastMessage from './ToastMessage.vue';
// A native modal makes the rest of the document inert. Keep messages in the
// active dialog so they remain visible, announced and keyboard-dismissible.
const target = ref<HTMLElement>(document.body);
let observer: MutationObserver;
function updateTarget() {
  for (const item of toasts.value) if (item.fieldId && !document.getElementById(item.fieldId)) dismissToast(item.id);
  const dialogs = document.querySelectorAll<HTMLDialogElement>('dialog[open]');
  target.value = dialogs.item(dialogs.length - 1) || document.body;
}
onMounted(() => {
  updateTarget(); observer = new MutationObserver(updateTarget);
  observer.observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['open'] });
});
onBeforeUnmount(() => observer?.disconnect());
</script>
<template>
  <Teleport :to="target">
    <aside class="toast-stack" role="status" aria-live="polite" aria-relevant="additions text" aria-label="Action notifications">
      <TransitionGroup name="toast"><ToastMessage v-for="item in toasts" :key="item.id" :item="item" /></TransitionGroup>
    </aside>
  </Teleport>
</template>
