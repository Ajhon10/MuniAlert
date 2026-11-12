<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, watch, type ComponentPublicInstance } from 'vue';
import { reducedMotion } from '../services/accessibility';
const props = withDefaults(defineProps<{ value: number; minimumDigits?: number }>(), { minimumDigits: 1 });
const finalValue = computed(() => new Intl.NumberFormat('en-PH', { maximumFractionDigits: 0, minimumIntegerDigits: props.minimumDigits }).format(props.value));
const parts = computed(() => [...finalValue.value].map((character, index, all) => ({
  character, digit: /^\d$/.test(character) ? Number(character) : null,
  key: `${/^\d$/.test(character) ? 'digit' : 'separator'}-${all.length - index - 1}`,
})));
const rows = Array.from({ length: 40 }, (_, index) => index % 10);
const tracks = new Map<string, HTMLElement>();
const previous = new Map<string, number>();
const animations = new Set<Animation>();
let generation = 0; let disposed = false;
const position = (digit: number) => `translateY(${-digit * 1.15}em)`;
function bindTrack(element: Element | ComponentPublicInstance | null, key: string) {
  if (element instanceof HTMLElement) tracks.set(key, element); else tracks.delete(key);
}
function stop() { generation++; animations.forEach(animation => animation.cancel()); animations.clear(); }
watch([finalValue, reducedMotion], async () => {
  stop(); const current = generation;
  await nextTick();
  if (disposed || current !== generation) return;
  const digits = parts.value.filter(part => part.digit !== null);
  for (const [index, part] of digits.entries()) {
    const digit = part.digit!; const track = tracks.get(part.key); const from = previous.get(part.key) ?? 0;
    previous.set(part.key, digit);
    if (!track || reducedMotion.value || !Number.isFinite(props.value)) continue;
    const animation = track.animate([{ transform: position(from) }, { transform: position(30 + digit) }], {
      duration: 950 + Math.min(index, 6) * 100, easing: 'cubic-bezier(.12,.68,.12,1)', fill: 'forwards',
    });
    animations.add(animation);
    animation.onfinish = () => { animation.cancel(); animations.delete(animation); };
  }
  for (const key of previous.keys()) if (!digits.some(part => part.key === key)) previous.delete(key);
}, { immediate: true, flush: 'post' });
onBeforeUnmount(() => { disposed = true; stop(); tracks.clear(); });
</script>
<template>
  <span class="animated-number rolling-number">
    <span class="number-reels" aria-hidden="true">
      <template v-for="part in parts" :key="part.key">
        <span v-if="part.digit !== null" class="number-reel"><span :ref="element => bindTrack(element, part.key)" class="number-reel-track" :style="{ transform: position(part.digit) }"><span v-for="(digit, index) in rows" :key="index" class="number-reel-digit">{{ digit }}</span></span></span>
        <span v-else class="number-separator">{{ part.character }}</span>
      </template>
    </span>
    <span class="visually-hidden">{{ finalValue }}</span>
  </span>
</template>
<style scoped>
.rolling-number { display: inline-flex; vertical-align: bottom; font-variant-numeric: tabular-nums; }
.number-reels { display: inline-flex; align-items: center; line-height: 1.15; user-select: none; }
.number-reel { display: inline-block; overflow: hidden; width: 1ch; height: 1.15em; text-align: center; }
.number-reel-track, .number-reel-digit { display: block; }
.number-reel-digit { height: 1.15em; line-height: 1.15; }
.number-separator { line-height: 1.15; }
</style>
