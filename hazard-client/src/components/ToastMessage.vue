<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { CheckCircle2, CircleAlert, Info, X } from '@lucide/vue';
import { dismissToast, type Toast } from '../services/feedback';
import { reading } from '../services/accessibility';
const props = defineProps<{ item: Toast }>();
const hovered = ref(false); const focused = ref(false); const hidden = ref(document.hidden);
let timer: ReturnType<typeof setTimeout> | undefined;
let remaining = 8000; let started = 0;
function pause() { if (timer) { clearTimeout(timer); remaining = Math.max(0, remaining - (performance.now() - started)); timer = undefined; } }
function schedule() {
  pause();
  if (props.item.tone === 'error' || reading.keepMessages || hovered.value || focused.value || hidden.value) return;
  started = performance.now(); timer = setTimeout(() => dismissToast(props.item.id), remaining);
}
function visibility() { hidden.value = document.hidden; }
function focusOut(event: FocusEvent) { if (!(event.currentTarget as HTMLElement).contains(event.relatedTarget as Node | null)) focused.value = false; }
function focusField() { if (props.item.fieldId) document.getElementById(props.item.fieldId)?.focus(); }
watch([hovered, focused, hidden, () => reading.keepMessages], schedule);
onMounted(() => { schedule(); document.addEventListener('visibilitychange', visibility); });
onBeforeUnmount(() => { pause(); document.removeEventListener('visibilitychange', visibility); });
</script>
<template>
  <article class="toast toast-message" :class="item.tone" @mouseenter="hovered = true" @mouseleave="hovered = false" @focusin="focused = true" @focusout="focusOut">
    <component :is="item.tone === 'success' ? CheckCircle2 : item.tone === 'error' ? CircleAlert : Info" :size="22" aria-hidden="true" />
    <div><strong>{{ item.tone === 'error' ? 'Please check' : item.tone === 'success' ? 'Done' : 'Good to know' }}</strong><p :id="`toast-message-${item.id}`">{{ item.message }}</p><button v-if="item.fieldId" type="button" class="text-button toast-field-link" :aria-controls="item.fieldId" @click="focusField">Go to field</button></div>
    <button type="button" class="icon-button" aria-label="Dismiss notification" :aria-describedby="`toast-message-${item.id}`" @click="dismissToast(item.id)"><X :size="20" aria-hidden="true" /></button>
  </article>
</template>
