<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';
import { Info } from '@lucide/vue';

defineProps<{ label: string; text: string }>();
const id = useId();
const trigger = ref<HTMLButtonElement>();
const tooltip = ref<HTMLElement>();
const open = ref(false);
const position = ref({ left: '12px', top: '12px' });
let pinned = false;
let timer: ReturnType<typeof setTimeout>;
function show() { clearTimeout(timer); open.value = true; }
function hide() { clearTimeout(timer); pinned = false; open.value = false; }
function leave() { if (!pinned) timer = setTimeout(hide, 160); }
function toggle() { pinned = !pinned; if (pinned) show(); else hide(); }
function place() {
  if (!trigger.value || !tooltip.value) return;
  const anchor = trigger.value.getBoundingClientRect();
  const box = tooltip.value.getBoundingClientRect();
  const left = Math.max(12, Math.min(anchor.left, innerWidth - box.width - 12));
  const top = anchor.bottom + box.height + 20 <= innerHeight ? anchor.bottom + 8 : Math.max(12, anchor.top - box.height - 8);
  position.value = { left: `${left}px`, top: `${top}px` };
}
function outside(event: PointerEvent) { if (!trigger.value?.contains(event.target as Node) && !tooltip.value?.contains(event.target as Node)) hide(); }
function escape(event: KeyboardEvent) { if (event.key === 'Escape') hide(); }
function cleanup() {
  document.removeEventListener('pointerdown', outside);
  document.removeEventListener('keydown', escape);
  window.removeEventListener('scroll', place, true);
  window.removeEventListener('resize', place);
}
watch(open, async value => {
  cleanup();
  if (!value) return;
  document.addEventListener('pointerdown', outside);
  document.addEventListener('keydown', escape);
  window.addEventListener('scroll', place, true);
  window.addEventListener('resize', place);
  await nextTick(); place();
});
onBeforeUnmount(() => { clearTimeout(timer); cleanup(); });
</script>

<template>
  <button ref="trigger" type="button" class="info-trigger" :aria-label="label" :aria-describedby="open ? id : undefined" :aria-expanded="open" @pointerenter="event => { if (event.pointerType === 'mouse') show(); }" @pointerleave="leave" @focus="show" @blur="leave" @click="toggle"><Info :size="18" aria-hidden="true" /></button>
  <Teleport to="body"><div v-if="open" :id="id" ref="tooltip" class="info-tooltip" role="tooltip" :style="position" @pointerenter="show" @pointerleave="leave">{{ text }}</div></Teleport>
</template>

<style>
.info-trigger { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 32px; width: 32px; height: 32px; min-height: 32px; padding: 4px; border: 1px solid #b9cfc5; border-radius: 50%; color: #356859; background: #f4faf6; vertical-align: middle; }
.info-trigger:hover, .info-trigger[aria-expanded=true] { background: #dceee4; color: #204e3e; }
.info-tooltip { position: fixed; z-index: 260; width: max-content; max-width: min(340px, calc(100vw - 24px)); max-height: calc(100dvh - 24px); overflow-y: auto; padding: 12px 16px; border: 1px solid #395c4f; border-radius: 12px; background: #203f34; color: #fff; box-shadow: 0 8px 24px #16352a24; font-size: .875rem; font-weight: 400; line-height: 1.6; overflow-wrap: anywhere; }
.heading-with-info { display: flex; align-items: center; flex-wrap: wrap; gap: 6px 10px; }
@media (pointer: coarse) { .info-trigger { flex-basis: 44px; width: 44px; height: 44px; min-height: 44px; } }
</style>
