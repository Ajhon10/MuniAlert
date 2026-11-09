<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import { apiBlob } from '../services/api';
import type { Hazard } from '../types';
const props = defineProps<{ photos: Hazard['photos']; kind: 'incident' | 'resolution' }>();
const images = ref<{ id: string; url: string }[]>([]);
const loading = ref(false); const error = ref(''); let generation = 0;
function clear() { images.value.forEach(item => URL.revokeObjectURL(item.url)); images.value = []; }
watch(() => props.photos, async photos => {
  const version = ++generation; clear(); error.value = ''; loading.value = true;
  try {
    const selected = photos.filter(photo => (photo.photo_type || 'incident') === props.kind);
    for (const photo of selected) {
      const blob = await apiBlob(photo.url);
      if (version !== generation) return;
      images.value.push({ id: photo.id, url: URL.createObjectURL(blob) });
    }
  } catch (e) { if (version === generation) error.value = (e as Error).message; }
  finally { if (version === generation) loading.value = false; }
}, { immediate: true });
onBeforeUnmount(() => { generation++; clear(); });
</script>
<template><section v-if="photos.some(photo => (photo.photo_type || 'incident') === kind)" class="evidence-section">
  <h3>{{ kind === 'incident' ? 'Incident photos' : 'Resolution photos' }} <span class="pill">Private</span></h3>
  <p v-if="loading" class="field-help" role="status">Loading private evidence…</p>
  <div class="detail-photos"><a v-for="(photo, index) in images" :key="photo.id" :href="photo.url" target="_blank" rel="noreferrer" :aria-label="`Open ${kind} photo ${index + 1}`"><img :src="photo.url" :alt="`${kind === 'incident' ? 'Incident' : 'Resolution'} evidence photo ${index + 1}`"></a></div>
  <p v-if="error" class="form-error" role="alert">{{ error }}</p>
</section></template>
