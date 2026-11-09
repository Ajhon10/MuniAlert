<script setup lang="ts">
import { ChevronRight, MapPin } from '@lucide/vue';
import HazardIcon from './HazardIcon.vue';
import { labels, relativeTime, type Hazard } from '../types';

defineProps<{ reports: Hazard[] }>();
defineEmits<{ select: [report: Hazard] }>();
</script>

<template>
  <div class="mobile-report-list">
    <button v-for="report in reports" :key="report.id" class="report-summary" :aria-label="`Open ${report.reference_number}`" @click="$emit('select', report)">
      <span class="category-icon" :class="report.category.id"><HazardIcon :category="report.category.id" /></span>
      <span class="report-summary-content">
        <span class="report-summary-title">{{ report.title }}</span>
        <span class="report-summary-location"><MapPin :size="14" aria-hidden="true" />{{ report.barangay }}</span>
        <span class="report-summary-meta">{{ report.reference_number }} · {{ relativeTime(report.created_at) }}</span>
        <span class="report-summary-tags"><span class="status" :class="report.status">{{ labels[report.status] }}</span><span class="severity" :class="report.severity">{{ report.severity }} priority</span></span>
      </span>
      <ChevronRight :size="18" aria-hidden="true" />
    </button>
  </div>
</template>
