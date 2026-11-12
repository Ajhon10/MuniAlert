<script setup lang="ts">
import FormSelect from './FormSelect.vue';
import FormInput from './FormInput.vue';
import { computed } from 'vue';
import { Layers, LocateFixed, ChevronDown, Map as MapIcon } from '@lucide/vue';
import type { Category } from '../types';
import { boundaryLevels, mapModes } from '../config/maps';
import { mapPreferences } from '../services/mapPreferences';
defineProps<{ categories: Category[]; category: string; visible: number; total: number; loading: boolean; zoom: number }>();
const emit = defineEmits<{ category: [value: string]; fit: []; area: []; zoom: [level: number] }>();
const enabled = computed(() => boundaryLevels.filter(level => mapPreferences.value.boundaries[level.id]));
const boundaryLabels = { province: 'Province boundaries', city: 'City and municipality boundaries', barangay: 'Barangay boundaries' };
</script>
<template><div class="map-controls" aria-label="Map display controls">
  <div class="map-control-row"><fieldset class="map-mode-picker"><legend><MapIcon :size="16" aria-hidden="true" />Map style</legend><div class="map-mode-buttons"><button v-for="mode in mapModes" :key="mode.id" type="button" :aria-pressed="mapPreferences.mode === mode.id" :aria-label="`${mode.label} map`" :title="mode.description" :class="['map-mode-button', mode.id]" @click="mapPreferences.mode = mode.id"><i aria-hidden="true"></i>{{ mode.label }}</button></div></fieldset>
  <label class="map-category">Show hazard type<FormSelect :value="category" aria-label="Map hazard category" @change="emit('category', ($event.target as HTMLSelectElement).value)"><option value="">All hazard types</option><option v-for="item in categories" :key="item.id" :value="item.id">{{ item.name }}</option></FormSelect></label></div>
  <div class="map-tools-row"><details class="boundary-options"><summary><Layers :size="18" aria-hidden="true" />Boundaries<span class="boundary-count">{{ enabled.length ? `${enabled.length} on` : 'Off' }}</span><ChevronDown :size="16" aria-hidden="true" /></summary><div class="boundary-options-body"><p>Show area borders</p><label v-for="level in boundaryLevels" :key="level.id"><FormInput v-model="mapPreferences.boundaries[level.id]" type="checkbox" :aria-label="boundaryLabels[level.id]" /><i :style="{ '--boundary-color': level.color }" :class="level.id" aria-hidden="true"></i><span>{{ level.label }}{{ level.id === 'barangay' ? ' · 13 in Basista' : '' }}</span></label><p class="boundary-help">Reference boundaries from published data (2023). City outlines cover Basista and nearby areas. These do not establish official jurisdiction.</p><div v-for="level in enabled.filter(item => zoom < item.minZoom)" :key="level.id" class="boundary-zoom-hint"><span>Zoom in for {{ level.label.toLowerCase() }}.</span><button type="button" class="text-button" @click="emit('zoom', level.minZoom + 0.5)">Zoom in</button></div></div></details><button type="button" class="map-fit-button" @click="emit('area')"><MapIcon :size="18" aria-hidden="true" />Basista area</button><button type="button" class="map-fit-button" :disabled="!visible" @click="emit('fit')"><LocateFixed :size="18" aria-hidden="true" />Fit reports</button></div>
  <p class="map-results" role="status">{{ loading ? 'Updating map reports…' : `${visible} ${visible === 1 ? 'report' : 'reports'} on this page · ${total} matching ${total === 1 ? 'report' : 'reports'}` }}<span v-if="category && !loading"> <button type="button" class="text-button" @click="emit('category', '')">Show all hazard types</button></span></p>
</div></template>
