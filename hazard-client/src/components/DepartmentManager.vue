<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Building2, Plus, Search } from '@lucide/vue';
import type { Category, Department } from '../types';
import { api } from '../services/api';
import { notify, useFeedback } from '../services/feedback';
import Modal from './Modal.vue';
import DataTable from './DataTable.vue';
import AppForm from './AppForm.vue';
import FormInput from './FormInput.vue';
import FormSelect from './FormSelect.vue';
const emit = defineEmits<{ changed: [] }>();
const departments = ref<Department[]>([]); const categories = ref<Category[]>([]); const search = ref('');
const error = ref(''); const busy = ref(false); const loading = ref(true);
const draft = ref<{ id: string; name: string; is_active: boolean; existing: boolean } | null>(null);
const routing = ref<Record<string, string>>({});
useFeedback([error]);
const filtered = computed(() => departments.value.filter(d => `${d.name} ${d.id}`.toLowerCase().includes(search.value.toLowerCase())));
async function load() {
  loading.value = true;
  try { const result = await api<{ departments: Department[]; categories: Category[] }>('/api/admin/departments'); departments.value = result.departments; categories.value = result.categories; routing.value = Object.fromEntries(result.categories.map(c => [c.id, c.department_id])); }
  catch (e) { error.value = (e as Error).message; } finally { loading.value = false; }
}
onMounted(load);
async function save() {
  if (!draft.value || busy.value) return; busy.value = true; error.value = '';
  try { const { id, name, is_active, existing } = draft.value; await api(existing ? `/api/admin/departments/${encodeURIComponent(id)}` : '/api/admin/departments', { method: existing ? 'PATCH' : 'POST', body: JSON.stringify({ id, name, is_active }) }); draft.value = null; await load(); emit('changed'); notify('Department saved.'); }
  catch (e) { error.value = (e as Error).message; } finally { busy.value = false; }
}
async function saveRouting(category: Category) {
  if (busy.value) return; busy.value = true; error.value = '';
  try { await api(`/api/admin/categories/${category.id}/routing`, { method: 'PATCH', body: JSON.stringify({ department_id: routing.value[category.id] }) }); await load(); emit('changed'); notify('Routing saved. New reports use the selected office.'); }
  catch (e) { error.value = (e as Error).message; } finally { busy.value = false; }
}
</script>
<template>
  <section class="department-manager cms-card">
    <header class="management-heading"><div><span class="eyebrow">Municipal operations / Configuration</span><h2><Building2 :size="22" aria-hidden="true" /> Departments & report routing</h2><p class="field-help">Manage responsible offices and assign each hazard type. Existing reports keep their assigned office.</p></div><button class="button primary" @click="draft = { id: '', name: '', is_active: true, existing: false }"><Plus :size="18" />Add department</button></header>
    <div class="management-toolbar"><label class="search-field"><Search :size="18" aria-hidden="true" /><FormInput v-model="search" type="search" aria-label="Search departments" placeholder="Search departments" /></label><span>{{ filtered.length }} offices</span></div>
    <p v-if="loading" role="status">Loading departments…</p>
    <div v-else><DataTable :rows="filtered" :columns="[{ label: 'Office' }, { label: 'Status' }, { label: 'Hazard types' }, { label: 'Actions' }]"><template #row="{ row: department }"><td><strong>{{ department.name }}</strong><small>{{ department.id }}</small></td><td>{{ department.is_active ? 'Active' : 'Archived' }}</td><td>{{ categories.filter(c => c.department_id === department.id).length }}</td><td><button class="button secondary" :aria-label="`Edit ${department.name}`" @click="draft = { ...department, is_active: !!department.is_active, existing: true }">Edit</button></td></template></DataTable><p v-if="!filtered.length" class="field-help">No matching departments.</p></div>
    <h3>Hazard routing</h3><div class="department-routing"><AppForm v-for="category in categories" :key="category.id" @submit.prevent="saveRouting(category)"><label>{{ category.name }}<FormSelect v-model="routing[category.id]" :aria-label="`Responsible office for ${category.name}`"><option v-for="department in departments.filter(d => d.is_active)" :key="department.id" :value="department.id">{{ department.name }}</option></FormSelect></label><button class="button secondary" :disabled="busy || routing[category.id] === category.department_id">Save routing</button></AppForm></div>
  </section>
  <Modal v-if="draft" :title="draft.existing ? 'Edit department' : 'Add department'" @close="draft = null"><AppForm id="department-form" class="form-body" @submit.prevent="save"><label>Office identifier<FormInput v-model="draft.id" name="id" :readonly="draft.existing" placeholder="e.g. health" /></label><label>Office name<FormInput v-model="draft.name" name="name" autocomplete="organization" /></label><label v-if="draft.existing" class="checkbox-label"><FormInput v-model="draft.is_active" type="checkbox" name="is_active" />Active office</label><p class="field-help">Reassign its hazard types before archiving an office. Existing reports and their history are preserved.</p></AppForm><template #footer><button class="button primary" type="submit" form="department-form" :disabled="busy">{{ busy ? 'Saving…' : 'Save department' }}</button></template></Modal>
</template>
<style scoped>
.department-manager { grid-column: 1 / -1; }
.management-heading, .management-toolbar { display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 16px; margin-bottom: 22px; }
.management-heading h2 { display: flex; align-items: center; gap: 10px; }
.management-heading > div { flex: 1 1 300px; }
.management-toolbar .search-field { flex: 1 1 250px; max-width: 480px; }
td small { display: block; color: var(--text-muted); }
.department-routing { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 16px; margin-top: 18px; }
.department-routing form { display: grid; gap: 10px; align-content: start; }
.department-routing label { display: grid; gap: 8px; }
.department-routing select { width: 100%; }
</style>
