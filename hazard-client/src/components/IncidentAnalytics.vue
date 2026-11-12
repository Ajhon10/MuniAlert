<script setup lang="ts">
import AppForm from './AppForm.vue';
import FormInput from './FormInput.vue';
import FormSelect from './FormSelect.vue';
import { notify, useFeedback } from '../services/feedback';
import { computed, onMounted, ref } from 'vue';
import { Download, RefreshCw } from '@lucide/vue';
import AnimatedNumber from './AnimatedNumber.vue';
import { api } from '../services/api';
import type { Category } from '../types';
defineProps<{ categories: Category[] }>();
type Period = { period: string; total: number; accidents: number; known_time: number };
type Summary = { from: string; to: string; group: 'week' | 'month'; total: number; known_time: number; date_basis: string; periods: Period[] };
const today = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Manila', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
const from = ref(`${today.slice(0, 4)}-01-01`); const to = ref(today); const group = ref('month'); const category = ref('');
const data = ref<Summary>(); const error = ref(''); const busy = ref(false);
const maximum = computed(() => Math.max(1, ...data.value?.periods.map(row => row.total) || []));
useFeedback([error], []);
async function load() {
  busy.value = true; error.value = '';
  try { data.value = await api<Summary>(`/api/admin/analytics?${new URLSearchParams({ from: from.value, to: to.value, group: group.value, ...(category.value ? { category: category.value } : {}) })}`); }
  catch (e) { error.value = (e as Error).message; }
  finally { busy.value = false; }
}
function exportCsv() {
  if (!data.value) return;
  const rows = [['Period starting (PHT)', 'Reports', 'Road accident reports', 'Known incident time', 'Submission-time fallback'], ...data.value.periods.map(row => [row.period, row.total, row.accidents, row.known_time, row.total - row.known_time])];
  const url = URL.createObjectURL(new Blob(['\uFEFF' + rows.map(row => row.join(',')).join('\r\n')], { type: 'text/csv;charset=utf-8' }));
  const link = document.createElement('a'); link.href = url; link.download = `munialert-${data.value.group}-${data.value.from}-${data.value.to}.csv`; link.click(); URL.revokeObjectURL(url); notify('Incident summary prepared for download.');
}
async function updateSummary() { await load(); if (!error.value) notify('Incident summary updated.'); }
onMounted(load);
</script>
<template><section class="cms-card incident-analytics" :aria-busy="busy"><h2>Incident records by period</h2>
  <p class="field-help" lang="fil">Pagsama-samahin ang mga ulat bawat linggo o buwan.</p>
  <AppForm class="form-body analytics-filters" @submit.prevent="updateSummary"><div class="form-grid"><label>From date<FormInput v-model="from" type="date" required name="from" /></label><label>To date<FormInput v-model="to" type="date" :min="from" required name="to" /></label><label>Group records<FormSelect v-model="group" name="group"><option value="month">Monthly</option><option value="week">Weekly · Monday start</option></FormSelect></label><label>Incident category<FormSelect v-model="category" name="category"><option value="">All categories</option><option v-for="item in categories" :key="item.id" :value="item.id">{{ item.name }}</option></FormSelect></label></div><button class="button primary" :disabled="busy"><RefreshCw :size="18" aria-hidden="true" />{{ busy ? 'Loading records…' : 'Update summary' }}</button></AppForm>

  <template v-if="data"><div class="analytics-heading"><div><strong class="analytics-total"><AnimatedNumber :value="data.total" /></strong><span> reports · {{ data.from }} to {{ data.to }} PHT</span></div><button class="button secondary" :disabled="busy" @click="exportCsv"><Download :size="18" aria-hidden="true" />Export summary</button></div>
  <p class="notice neutral">{{ data.date_basis }} {{ data.total - data.known_time }} report(s) use submission time because incident time was not supplied. Counts describe reports, not confirmed casualties or unique incidents.</p>
  <p class="field-help">{{ data.group === 'week' ? 'Week' : 'Month' }} starting dates are shown below. First and last periods may be partial. Counts alone do not establish the cause of accidents.</p>
  <div class="analytics-periods"><article v-for="row in data.periods" :key="row.period" class="analytics-period"><div><h3>{{ row.period }}</h3><strong><AnimatedNumber :value="row.total" /> reports</strong></div><div class="analytics-bar" aria-hidden="true"><i :style="{ width: `${row.total / maximum * 100}%` }"></i></div><p>{{ row.accidents }} road accident reports · {{ row.total - row.known_time }} with unknown incident time</p></article></div></template>
</section></template>
