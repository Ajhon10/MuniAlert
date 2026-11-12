<script setup lang="ts">
import { computed, ref, useId } from 'vue';
import type { IncidentStatistics } from '../types/statistics';
const props = defineProps<{ data: IncidentStatistics }>();
const id = useId(); const focused = ref<number | null>(null);
const palette = ['#23674f', '#3769a0', '#b15c31', '#7a5594', '#9b6a13', '#388284', '#9e4c65', '#526c37', '#675d4d', '#607992'];
const maximum = computed(() => Math.ceil(Math.max(1, ...props.data.periods.map(row => row.total)) / 4) * 4);
const step = computed(() => 840 / Math.max(1, props.data.periods.length));
const labelStep = computed(() => Math.max(1, Math.ceil(props.data.periods.length / 9)));
const barWidth = computed(() => Math.max(1, Math.min(56, step.value - 3)));
function barX(index: number) { return 58 + index * step.value + (step.value - barWidth.value) / 2; }
function bars(index: number) {
  let total = 0;
  return props.data.categories.map((category, i) => {
    const value = props.data.periods[index]?.categories[category.id] || 0;
    const height = value / maximum.value * 260; const bottom = total; total += height;
    return { id: category.id, name: category.name, value, height, y: 290 - bottom - height, color: palette[i % palette.length] };
  });
}
const categoryMax = computed(() => Math.max(1, ...props.data.categories.map(item => item.total)));
</script>
<template>
  <div class="statistics-charts">
    <section class="statistics-trend" :aria-labelledby="`${id}-trend`"><h3 :id="`${id}-trend`">Reports over time</h3><p class="field-help">Each colour represents a hazard type. Select a period for its breakdown, or use the data table below.</p>
      <div class="chart-scroll" tabindex="0" aria-label="Scrollable report trend chart"><svg viewBox="0 0 920 350" role="img" :aria-labelledby="`${id}-chart-title`" :aria-describedby="`${id}-chart-desc`"><title :id="`${id}-chart-title`">{{ data.group }} report counts by hazard category</title><desc :id="`${id}-chart-desc`">{{ data.total }} reports from {{ data.from }} to {{ data.to }} in Philippine time. The data table contains every period and category count.</desc>
        <g v-for="tick in 5" :key="tick"><line x1="56" x2="900" :y1="290 - (tick - 1) * 65" :y2="290 - (tick - 1) * 65" stroke="#dce7e0" /><text x="46" :y="294 - (tick - 1) * 65" text-anchor="end">{{ Math.round(maximum * (tick - 1) / 4) }}</text></g>
        <g v-for="(period, index) in data.periods" :key="period.period" tabindex="0" role="button" :aria-label="`${period.period}: ${period.total} reports`" @click="focused = index" @keydown.enter.prevent="focused = index" @keydown.space.prevent="focused = index"><title>{{ period.period }}: {{ period.total }} reports</title><rect :x="58 + index * step" y="28" :width="Math.max(1, step - 2)" height="264" fill="transparent" /><rect v-for="bar in bars(index).filter(item => item.value)" :key="bar.id" :x="barX(index)" :y="bar.y" :width="barWidth" :height="bar.height" :fill="bar.color"><title>{{ bar.name }}: {{ bar.value }}</title></rect><text v-if="index % labelStep === 0" :x="58 + (index + .5) * step" y="320" text-anchor="middle">{{ data.group === 'year' ? period.period.slice(0, 4) : period.period.slice(5) }}</text></g>
      </svg></div>
      <div class="chart-legend"><span v-for="(category, index) in data.categories" :key="category.id"><i :style="{ backgroundColor: palette[index % palette.length] }" aria-hidden="true"></i>{{ category.name }}</span></div>
      <div v-if="focused !== null && data.periods[focused]" class="chart-breakdown" role="status"><strong>{{ data.periods[focused]!.period }} · {{ data.periods[focused]!.total }} reports</strong><span v-for="category in data.categories" :key="category.id">{{ category.name }}: {{ data.periods[focused]!.categories[category.id] || 0 }}</span></div>
    </section>
    <section class="statistics-categories"><h3>Hazard category totals</h3><div v-for="(category, index) in data.categories" :key="category.id" class="category-stat"><span>{{ category.name }}</span><strong>{{ category.total.toLocaleString() }}</strong><div class="category-stat-track" aria-hidden="true"><i :style="{ width: `${category.total / categoryMax * 100}%`, backgroundColor: palette[index % palette.length] }"></i></div></div></section>
  </div>
</template>
