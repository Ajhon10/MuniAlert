<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { reducedMotion } from '../services/accessibility';
import RollingNumber from './RollingNumber.vue';
const props = withDefaults(defineProps<{ value: number; minimumDigits?: number; rolling?: boolean }>(), { minimumDigits: 1, rolling: false });
const display = ref(0); let frame = 0;
const format = (value: number) => new Intl.NumberFormat('en-PH', { maximumFractionDigits: 0, minimumIntegerDigits: props.minimumDigits }).format(value);
const finalValue = computed(() => format(props.value));
watch([() => props.value, reducedMotion, () => props.rolling], ([target, reduce, rolling]) => {
  cancelAnimationFrame(frame);
  if (rolling || reduce || !Number.isFinite(target)) { display.value = target; return; }
  const from = display.value; const start = performance.now();
  function step(now: number) {
    const progress = Math.min(1, (now - start) / 650);
    display.value = from + (target - from) * (1 - (1 - progress) ** 3);
    if (progress < 1) frame = requestAnimationFrame(step);
    else display.value = target;
  }
  frame = requestAnimationFrame(step);
}, { immediate: true });
onBeforeUnmount(() => cancelAnimationFrame(frame));
</script>
<template><RollingNumber v-if="rolling" :value="value" :minimum-digits="minimumDigits" /><span v-else class="animated-number"><span aria-hidden="true">{{ format(display) }}</span><span class="visually-hidden">{{ finalValue }}</span></span></template>
