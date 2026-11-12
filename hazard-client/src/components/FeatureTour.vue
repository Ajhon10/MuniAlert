<script setup lang="ts">
import { useNativeDialog } from '../composables/useNativeDialog';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { ArrowRight, ArrowLeft, X, Sparkles } from '@lucide/vue';
const props = defineProps<{ admin: boolean }>();
const emit = defineEmits<{ close: []; manual: [] }>();
const dialog = useNativeDialog(); const heading = ref<HTMLHeadingElement>(); const index = ref(0);
const box = ref({ left: 16, top: 100, width: 300, height: 100 }); const panelAbove = ref(false);
const steps = computed(() => [
  { selector: '.page-heading', title: 'Your municipal workspace', body: 'Find the main task for each screen here. The map and records below help you see what is happening around your community.' },
  props.admin ? { selector: '.metrics', title: 'See the current picture', body: 'These totals summarize submitted records and their current status. Reports are reviewed by municipal staff; counts are not confirmed casualty figures.' } : { selector: '.report-button', title: 'Start a report', body: 'Choose Report a hazard to add safe photos, a clear description and a confirmed incident location. Call emergency services first when someone is in danger.' },
  { selector: '.filter-bar', title: 'Find the right record', body: 'Search by the fields available to you, then narrow the results by incident category or status. Clear filters to return to the full list.' },
  { selector: '.map-panel', title: 'Read the incident map', body: props.admin ? 'Open a report to inspect the exact selected and verified locations. Original phone GPS stays separate from the incident pin.' : 'Community markers show approximate areas. Original photos, exact locations and sensitive incident details stay private.' },
  { selector: '.reports-panel', title: props.admin ? 'Review and document action' : 'Open a report and follow progress', body: props.admin ? 'Open a record to verify evidence, set priority, assign a department/team and save response notes. Add separate resolution photos as action is completed.' : 'Cards summarize the reports you can browse. My reports holds your private timeline, response notes and incident/resolution photos after sign-in.' },
  { selector: '.top-avatar', title: 'Keep the guide within reach', body: 'Open the account menu for My profile, Settings and Log out. Guests can sign in from the header. Settings contains the user guide, reading preferences and account security, including privacy and daily/monthly/yearly statistics.' },
]);
const current = computed(() => steps.value[index.value]!);
async function position() {
  await nextTick(); const element = document.querySelector<HTMLElement>(current.value.selector);
  if (!element) return;
  element.scrollIntoView({ block: 'center', behavior: 'instant' });
  const rect = element.getBoundingClientRect();
  const left = Math.max(8, rect.left - 5); const top = Math.max(8, rect.top - 5);
  box.value = { left, top, width: Math.max(1, Math.min(innerWidth - 8, rect.right + 5) - left), height: Math.max(1, Math.min(innerHeight - 8, rect.bottom + 5) - top) };
  panelAbove.value = top + box.value.height / 2 > innerHeight * 0.52;
}
async function move(delta: number) { index.value = Math.min(steps.value.length - 1, Math.max(0, index.value + delta)); await position(); heading.value?.focus({ preventScroll: true }); }
onMounted(async () => { await position(); window.addEventListener('resize', position); window.addEventListener('scroll', positionFromScroll, { passive: true }); });
function positionFromScroll() { const rect = document.querySelector<HTMLElement>(current.value.selector)?.getBoundingClientRect(); if (!rect) return; const left = Math.max(8, rect.left - 5); const top = Math.max(8, rect.top - 5); box.value = { left, top, width: Math.max(1, Math.min(innerWidth - 8, rect.right + 5) - left), height: Math.max(1, Math.min(innerHeight - 8, rect.bottom + 5) - top) }; }
onBeforeUnmount(() => { window.removeEventListener('resize', position); window.removeEventListener('scroll', positionFromScroll); });
</script>
<template><dialog ref="dialog" class="feature-tour" aria-label="Feature tour" @cancel.prevent="emit('close')"><div class="tour-focus" :style="{ left: `${box.left}px`, top: `${box.top}px`, width: `${box.width}px`, height: `${box.height}px` }" aria-hidden="true"></div><section class="tour-sheet" :class="{ 'tour-sheet-above': panelAbove }"><header class="tour-header"><div class="tour-sheet-top"><span><Sparkles :size="18" aria-hidden="true" />Feature tour · {{ index + 1 }} of {{ steps.length }}</span><button class="icon-button" aria-label="Close feature tour" @click="emit('close')"><X :size="22" /></button></div><h2 ref="heading" tabindex="-1">{{ current.title }}</h2></header><div class="tour-body"><p>{{ current.body }}</p></div><footer class="tour-footer"><div class="tour-progress" aria-hidden="true"><i v-for="(_, step) in steps" :key="step" :class="{ current: step === index, complete: step < index }"></i></div><div class="tour-actions"><button class="button secondary" :disabled="index === 0" @click="move(-1)"><ArrowLeft :size="17" aria-hidden="true" />Back</button><button v-if="index < steps.length - 1" class="button primary" @click="move(1)">Next<ArrowRight :size="17" aria-hidden="true" /></button><button v-else class="button primary" @click="emit('manual')">Open user manual<ArrowRight :size="17" aria-hidden="true" /></button></div></footer></section></dialog></template>
