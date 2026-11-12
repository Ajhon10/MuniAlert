<script setup lang="ts">
import { UserRound } from '@lucide/vue';
import type { Hazard } from '../types';
defineProps<{ report: Hazard }>();
function date(value?: string | null) {
  return value ? new Date(value).toLocaleString('en-PH', { timeZone: 'Asia/Manila', dateStyle: 'medium', timeStyle: 'short' }) + ' PHT' : 'Not confirmed';
}
</script>
<template>
  <section class="news-incident-details" aria-label="Incident details">
    <h3>Incident details</h3>
    <p class="news-reporter"><UserRound :size="19" aria-hidden="true" /><span>Submitted by <strong>{{ report.public_reporter_name || 'Community member (name withheld)' }}</strong></span></p>
    <dl class="news-facts">
      <div><dt>Hazard</dt><dd>{{ report.category.name }}</dd></div>
      <div><dt>Area</dt><dd>{{ report.barangay }}</dd></div>
      <div><dt>Priority</dt><dd class="news-priority">{{ report.severity }}</dd></div>
      <div><dt>Incident time</dt><dd>{{ date(report.public_occurred_at) }}</dd></div>
      <div><dt>Reported</dt><dd>{{ date(report.created_at) }}</dd></div>
      <div><dt>Fact-checked</dt><dd>{{ date(report.fact_checked_at) }}</dd></div>
      <div><dt>Responsible office</dt><dd>{{ report.department?.name || 'Municipal response team' }}</dd></div>
      <div><dt>Reference</dt><dd>{{ report.reference_number }}</dd></div>
    </dl>
  </section>
</template>
