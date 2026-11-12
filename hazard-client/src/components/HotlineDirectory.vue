<script setup lang="ts">
import { computed, ref } from 'vue';
import { Phone, MapPin, LocateFixed } from '@lucide/vue';
import { currentLocation } from '../services/mediaService';
import { useFeedback } from '../services/feedback';
import type { Hotline, Point } from '../types';
const props = defineProps<{ hotlines: Hotline[] }>();
const search = ref(''); const origin = ref<Point>(); const locating = ref(false); const error = ref('');
useFeedback([error]);
async function locate() { locating.value = true; error.value = ''; try { origin.value = await currentLocation(); } catch { error.value = 'Location unavailable. Search by agency, service or area instead.'; } finally { locating.value = false; } }
function distance(line: Hotline): number | null {
  if (!origin.value || line.latitude == null || line.longitude == null) return null;
  const rad = Math.PI / 180; const lat = (line.latitude - origin.value.latitude) * rad; const lon = (line.longitude - origin.value.longitude) * rad;
  const value = Math.sin(lat / 2) ** 2 + Math.cos(origin.value.latitude * rad) * Math.cos(line.latitude * rad) * Math.sin(lon / 2) ** 2;
  return 6371 * 2 * Math.atan2(Math.sqrt(Math.min(1, value)), Math.sqrt(Math.max(0, 1 - value)));
}
const matches = computed(() => props.hotlines.filter(line => `${line.name} ${line.number} ${line.service || ''} ${line.area || ''} ${line.address || ''}`.toLowerCase().includes(search.value.trim().toLowerCase())).map(line => ({ ...line, distance: distance(line) })).sort((a, b) => (a.distance ?? Infinity) - (b.distance ?? Infinity) || a.name.localeCompare(b.name)));
</script>
<template>
  <section class="hotline-directory" aria-label="Emergency agency directory">
    <label class="hotline-search">Search hotlines<input v-model="search" aria-label="Search hotlines" type="search" placeholder="Name or number" maxlength="120"></label>
    <button v-if="hotlines.some(line => line.latitude != null && line.longitude != null)" type="button" class="button secondary" :disabled="locating" @click="locate"><LocateFixed :size="18" aria-hidden="true" />{{ locating ? 'Finding your location…' : 'Show closest agencies' }}</button>
    <ul v-if="matches.length" class="hotline-results"><li v-for="(line, index) in matches" :key="`${line.name}-${index}`"><div><strong>{{ line.name }}</strong><small v-if="line.service || line.area"><MapPin :size="15" aria-hidden="true" />{{ [line.service, line.area].filter(Boolean).join(' · ') }}</small><small v-if="line.address">{{ line.address }}</small><small v-if="line.distance != null">About {{ line.distance < 1 ? `${Math.round(line.distance * 1000)} m` : `${line.distance.toFixed(1)} km` }} away</small></div><a class="button secondary hotline-call" :href="`tel:${line.number.replace(/[^+0-9]/g, '')}`" :aria-label="`Call ${line.name} at ${line.number}`"><Phone :size="18" aria-hidden="true" />{{ line.number }}</a></li></ul>
    <p v-else class="notice neutral" role="status">{{ hotlines.length ? 'No matching hotline. Try another agency, service or area.' : 'Local agency numbers have not been published yet. You can continue with an online report.' }}</p>
    <p class="field-help">{{ origin ? 'Closest first. Distances are straight-line estimates; call to confirm availability.' : 'Search by agency, service, number or area.' }}</p>
  </section>
</template>
<style scoped>
.hotline-directory { display: grid; gap: 16px; min-width: 0; }
label.hotline-search { display: grid; align-items: start; gap: 10px; }
.hotline-search input { min-width: 0; width: 100%; }
.hotline-results { list-style: none; padding: 0; margin: 0; display: grid; gap: 12px; }
.hotline-results li { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px; border: 1px solid var(--border, #d7e3dc); border-radius: 16px; background: var(--surface, #fff); }
.hotline-results li > div { display: grid; gap: 8px; min-width: 0; overflow-wrap: anywhere; }
.hotline-results small { display: flex; align-items: center; gap: 6px; color: var(--muted, #52675e); }
.hotline-call { flex-shrink: 0; white-space: normal; overflow-wrap: anywhere; }
@media (max-width: 600px) { .hotline-results li { align-items: stretch; flex-direction: column; } }
:global(.largeText) .hotline-results li { align-items: stretch; flex-direction: column; }
</style>
