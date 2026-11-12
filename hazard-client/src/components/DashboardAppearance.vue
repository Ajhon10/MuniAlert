<script setup lang="ts">
import FormInput from './FormInput.vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import { ImagePlus, Trash2 } from '@lucide/vue';
import { api } from '../services/api';
import { API_SERVER_URL } from '../config/api';
import { municipalSettings } from '../services/municipalSettings';
import { notify, useFeedback } from '../services/feedback';
import ConfirmDialog from './ConfirmDialog.vue';
const file = ref<File | null>(null); const preview = ref(''); const error = ref(''); const busy = ref(false);
const pending = ref<'publish' | 'remove' | null>(null); const input = ref<HTMLInputElement>();
const published = computed(() => municipalSettings.value.dashboard_background ? API_SERVER_URL + municipalSettings.value.dashboard_background : '');
useFeedback([error]);
function clearDraft() { if (preview.value) URL.revokeObjectURL(preview.value); preview.value = ''; file.value = null; if (input.value) input.value.value = ''; }
onBeforeUnmount(clearDraft);
function choose(event: Event) {
  const selected = (event.target as HTMLInputElement).files?.[0]; clearDraft(); error.value = '';
  if (!selected) return;
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(selected.type) || selected.size > 1024 * 1024) { error.value = 'Choose a JPG, PNG or WebP image up to 1 MB.'; return; }
  file.value = selected; preview.value = URL.createObjectURL(selected);
}
async function save() {
  if (busy.value || !pending.value) return;
  busy.value = true; error.value = '';
  try {
    const data = new FormData(); if (file.value) data.append('image', file.value);
    const result = await api<{ dashboard_background: string | null }>('/api/admin/dashboard-background', pending.value === 'remove' ? { method: 'DELETE' } : { method: 'POST', body: data });
    municipalSettings.value = { ...municipalSettings.value, ...result };
    clearDraft(); notify(result.dashboard_background ? 'Dashboard background published.' : 'Default dashboard background restored.', 'success'); pending.value = null;
  } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; }
}
</script>
<template>
  <section class="cms-card dashboard-appearance">
    <h2><ImagePlus :size="21" aria-hidden="true" />Dashboard background</h2>
    <p class="field-help">Choose an approved community image. It appears on everyone's dashboard with a soft overlay to keep text readable. Avoid personal information or incident evidence.</p>
    <div class="background-preview" :class="{ 'has-image': preview || published }">
      <img v-if="preview || published" :src="preview || published" alt="Dashboard background preview">
      <div class="background-preview-content"><span>MuniAlert</span><strong>Community dashboard</strong><div><span>Active hazards</span><b>04</b></div></div>
    </div>
    <label class="background-picker">Choose background image<FormInput ref="input" type="file" accept="image/jpeg,image/png,image/webp" :disabled="busy" @change="choose" /></label>
    <p class="field-help">JPG, PNG or WebP · up to 1 MB · maximum 1920 pixels per side. A landscape image works best.</p>
    <div class="button-row background-actions">
      <button v-if="file" class="button primary" :disabled="busy" @click="pending = 'publish'">Publish dashboard background</button>
      <button v-if="file" class="button secondary" :disabled="busy" @click="clearDraft">Discard preview</button>
      <button v-if="published" class="text-button" :disabled="busy" @click="pending = 'remove'"><Trash2 :size="17" aria-hidden="true" />Remove background</button>
    </div>

    <ConfirmDialog v-if="pending" :title="pending === 'publish' ? 'Publish dashboard background?' : 'Remove dashboard background?'" :message="pending === 'publish' ? 'This approved image will be visible to everyone using the dashboard.' : 'The dashboard will return to its default appearance.'" :confirm-label="pending === 'publish' ? 'Publish background' : 'Remove background'" :busy="busy" :error="error" @close="pending = null" @confirm="save" />
  </section>
</template>
