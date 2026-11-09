<script setup lang="ts">
import { computed } from 'vue';
import { Car, Trees, Waves, Flame, Construction, Sprout, Lightbulb, Zap, Trash2, TriangleAlert } from '@lucide/vue';
const props = withDefaults(defineProps<{ category: string; size?: number }>(), { size: 20 });
const icons = { accident: Car, tree: Trees, flood: Waves, fire: Flame, road: Construction, vegetation: Sprout, streetlight: Lightbulb, electrical: Zap, waste: Trash2, other: TriangleAlert };
const icon = computed(() => icons[props.category as keyof typeof icons] || TriangleAlert);
</script>
<template><component :is="icon" :size="size" :stroke-width="1.8" /></template>
