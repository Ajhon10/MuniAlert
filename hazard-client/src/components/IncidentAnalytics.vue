<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Download, RefreshCw } from '@lucide/vue';
import AppForm from './AppForm.vue';
import FormInput from './FormInput.vue';
import FormSelect from './FormSelect.vue';
import AnimatedNumber from './AnimatedNumber.vue';
import StatisticsChart from './StatisticsChart.vue';
import DataTable from './DataTable.vue';
import { notify, useFeedback } from '../services/feedback';
import { api, statisticsExport } from '../services/api';
import type { Category } from '../types';
import type { IncidentStatistics, StatisticsGroup } from '../types/statistics';

defineProps<{ categories: Category[] }>();
const today = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Manila', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
const from = ref(`${today.slice(0, 4)}-01-01`); const to = ref(today);
const group = ref<StatisticsGroup>('month'); const category = ref(''); const basis = ref('submitted');
const format = ref('pdf'); const exporting = ref(false); const query = ref('');
const data = ref<IncidentStatistics>(); const error = ref(''); const busy = ref(false);
let generation = 0;
const rows = computed(() => data.value?.periods.map(row => ({ ...row, id: row.period })) || []);
const columns = computed(() => [{ label: 'Period starting (PHT)' }, { label: 'Total reports' }, ...data.value?.categories.map(item => ({ label: item.name })) || []]);
useFeedback([error], []);
async function load() {
  const current = ++generation; busy.value = true; error.value = '';
  const params = new URLSearchParams({ from: from.value, to: to.value, group: group.value, basis: basis.value, ...(category.value ? { category: category.value } : {}) }).toString();
  try { const result = await api<IncidentStatistics>(`/api/admin/analytics?${params}`); if (current === generation) { data.value = result; query.value = params; } }
  catch (e) { if (current === generation) error.value = (e as Error).message; }
  finally { if (current === generation) busy.value = false; }
}
async function exportSummary() {
  if (!data.value || exporting.value) return;
  exporting.value = true; error.value = '';
  const extension = format.value; const snapshot = data.value; const current = generation;
  try {
    const blob = await statisticsExport(`/api/admin/analytics/export/${extension}?${query.value}`);
    if (current !== generation) return;
    const url = URL.createObjectURL(blob); const link = document.createElement('a');
    link.href = url; link.download = `munialert-statistics-${snapshot.group}-${snapshot.from}-${snapshot.to}.${extension}`;
    document.body.append(link); link.click(); link.remove(); setTimeout(() => URL.revokeObjectURL(url), 60000);
    notify('Statistics report prepared for download.');
  } catch (e) { if (current === generation) error.value = (e as Error).message; }
  finally { exporting.value = false; }
}
async function updateSummary() { await load(); if (!error.value) notify('Incident summary updated.'); }
function preset(period: 'day' | 'month' | 'year') {
  group.value = period; to.value = today;
  from.value = period === 'day' ? `${today.slice(0, 7)}-01` : period === 'year' ? `${Number(today.slice(0, 4)) - 4}-01-01` : `${today.slice(0, 4)}-01-01`;
  void updateSummary();
}
onMounted(load); onUnmounted(() => { generation++; });
</script>
<template>
  <section class="cms-card incident-analytics" :aria-busy="busy">
    <div class="statistics-title"><div><span class="eyebrow">Municipal statistics</span><h2>Incident records by period</h2><p class="field-help">Track report counts by hazard type, using Philippine dates.</p></div><div class="statistics-presets" aria-label="Statistics date shortcuts"><button type="button" class="button secondary" :disabled="busy || exporting" @click="preset('day')">This month · daily</button><button type="button" class="button secondary" :disabled="busy || exporting" @click="preset('month')">This year · monthly</button><button type="button" class="button secondary" :disabled="busy || exporting" @click="preset('year')">Last 5 years</button></div></div>
    <AppForm class="form-body analytics-filters" @submit.prevent="updateSummary"><div class="form-grid"><label>From date<FormInput v-model="from" type="date" required name="from" /></label><label>To date<FormInput v-model="to" type="date" required name="to" /></label><label>Group records<FormSelect v-model="group" name="group"><option value="day">Daily</option><option value="week">Weekly · Monday start</option><option value="month">Monthly</option><option value="year">Yearly</option></FormSelect></label><label>Incident category<FormSelect v-model="category" name="category"><option value="">All categories</option><option v-for="item in categories" :key="item.id" :value="item.id">{{ item.name }}</option></FormSelect></label><label>Count reports by<FormSelect v-model="basis" name="basis"><option value="submitted">Date submitted</option><option value="incident">Incident date · submission fallback</option></FormSelect></label></div><p class="field-help">Daily, weekly and monthly ranges support up to 366 days. Yearly ranges support up to 20 calendar years.</p><button class="button primary" :disabled="busy || exporting"><RefreshCw :size="18" aria-hidden="true" />{{ busy ? 'Loading records…' : 'Update summary' }}</button></AppForm>
    <template v-if="data">
      <div class="analytics-heading"><div><strong class="analytics-total"><AnimatedNumber :value="data.total" /></strong><span> reports · {{ data.from }} to {{ data.to }} PHT · {{ data.group }}</span></div><div class="statistics-export"><label>Export format<FormSelect v-model="format" name="export_format"><option value="pdf">PDF (.pdf)</option><option value="docx">Word (.docx)</option><option value="xlsx">Excel (.xlsx)</option></FormSelect></label><button type="button" class="button secondary" :disabled="busy || exporting" @click="exportSummary"><Download :size="18" aria-hidden="true" />{{ exporting ? 'Preparing report…' : 'Export summary' }}</button></div></div>
      <p class="notice neutral">{{ data.date_basis }} Counts describe reports, not confirmed casualties or unique incidents.</p>
      <p v-if="data.basis === 'incident'" class="field-help">{{ data.total - data.known_time }} report(s) use submission time because incident time was not supplied.</p>
      <StatisticsChart :data="data" />
      <details class="statistics-table"><summary>View counts in a data table</summary><p class="field-help">{{ data.group }} starting dates are shown. First and last periods may be partial. Exports contain the displayed date range, date basis, chart and every category count.</p><DataTable :rows="rows" :columns="columns"><template #row="{ row }"><th scope="row">{{ row.period }}</th><td>{{ row.total }}</td><td v-for="item in data.categories" :key="item.id">{{ row.categories[item.id] || 0 }}</td></template></DataTable></details>
    </template>
  </section>
</template>
