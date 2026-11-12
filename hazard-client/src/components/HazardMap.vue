<script setup lang="ts">
import FormSelect from './FormSelect.vue';
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import * as maplibregl from 'maplibre-gl';
import mapWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import 'maplibre-gl/dist/maplibre-gl.css';
import type { Hazard, Point, GPS } from '../types';
import { municipalSettings, loadMunicipalSettings } from '../services/municipalSettings';
import { mapPreferences } from '../services/mapPreferences';
import { mapStyle, mapModes } from '../config/maps';
import { updateBoundaryLayers } from '../services/mapBoundaries';
import municipality from '../config/municipality.json';
import { reducedMotion } from '../services/accessibility';
maplibregl.setWorkerUrl(mapWorkerUrl);
const props = withDefaults(defineProps<{ reports?: Hazard[]; pin?: Point; gps?: GPS; editable?: boolean; selected?: string; styleSelector?: boolean }>(), { reports: () => [] });
const emit = defineEmits<{ pick: [point: Point]; select: [report: Hazard]; zoom: [value: number] }>();
const container = ref<HTMLDivElement>(); const failed = ref(false); const mapError = ref(false);
let map: maplibregl.Map | undefined; let markers: maplibregl.Marker[] = []; let pinMarker: maplibregl.Marker | undefined; let gpsMarker: maplibregl.Marker | undefined; let observer: ResizeObserver | undefined;
let ready = false; let styleReady = false; let styleTimer: ReturnType<typeof setTimeout>; let lastReportLocations = '';
const motion = () => reducedMotion.value ? 0 : 250;
function renderReports() {
  markers.forEach(marker => marker.remove()); markers = [];
  if (!map) return;
  props.reports.forEach(report => {
    const button = document.createElement('button'); button.type = 'button';
    button.className = `map-pin ${report.severity} ${report.id === props.selected ? 'selected' : ''} ${['resolved', 'closed'].includes(report.status) ? 'done' : ''}`;
    button.textContent = '!'; button.setAttribute('aria-label', `${report.category.name}: ${report.title}`);
    button.addEventListener('click', event => { event.stopPropagation(); emit('select', report); });
    markers.push(new maplibregl.Marker({ element: button }).setLngLat([report.verified_longitude ?? report.selected_longitude, report.verified_latitude ?? report.selected_latitude]).addTo(map!));
  });
}
function renderLocation() {
  if (!map) return;
  if (props.pin) {
    if (!pinMarker) {
      pinMarker = new maplibregl.Marker({ color: '#217564', draggable: !!props.editable }).setLngLat([props.pin.longitude, props.pin.latitude]).addTo(map);
      pinMarker.on('dragend', () => { const point = pinMarker!.getLngLat(); emit('pick', { latitude: Number(point.lat.toFixed(6)), longitude: Number(point.lng.toFixed(6)) }); });
    }
    pinMarker.setLngLat([props.pin.longitude, props.pin.latitude]).setDraggable(!!props.editable);
  } else { pinMarker?.remove(); pinMarker = undefined; }
  gpsMarker?.remove(); gpsMarker = undefined;
  if (props.gps) {
    const dot = document.createElement('div'); dot.className = 'gps-dot'; dot.title = `Device GPS: ±${Math.round(props.gps.accuracy)} m`;
    gpsMarker = new maplibregl.Marker({ element: dot }).setLngLat([props.gps.longitude, props.gps.latitude]).addTo(map);
  }
}
function reportSignature() { return props.reports.map(report => `${report.id}:${report.verified_latitude ?? report.selected_latitude}:${report.verified_longitude ?? report.selected_longitude}`).join('|'); }
function fitReports() {
  if (!map) return;
  if (props.pin) map.easeTo({ center: [props.pin.longitude, props.pin.latitude], zoom: 16, duration: motion() });
  else if (props.reports.length) {
    const bounds = new maplibregl.LngLatBounds(); props.reports.forEach(report => bounds.extend([report.verified_longitude ?? report.selected_longitude, report.verified_latitude ?? report.selected_latitude]));
    map.fitBounds(bounds, { padding: 55, maxZoom: 15, duration: 0 });
  }
}
function zoomTo(level: number) { map?.easeTo({ zoom: level, duration: motion() }); }
function fitServiceArea() { map?.fitBounds(municipality.bounds as [number, number, number, number], { padding: 28, duration: motion() }); }
function changeStyle() {
  if (!map) return;
  clearTimeout(styleTimer); mapError.value = false; styleReady = false;
  styleTimer = setTimeout(() => { mapError.value = true; }, 15000);
  try { map.setStyle(mapStyle(mapPreferences.value.mode)); } catch { mapError.value = true; clearTimeout(styleTimer); }
}
function pickCenter() { if (props.editable && map) { const point = map.getCenter(); emit('pick', { latitude: Number(point.lat.toFixed(6)), longitude: Number(point.lng.toFixed(6)) }); } }
defineExpose({ zoomTo, fitReports, fitServiceArea });
onMounted(() => {
  try {
    map = new maplibregl.Map({ container: container.value!, style: mapStyle(mapPreferences.value.mode), center: [municipalSettings.value.map.longitude, municipalSettings.value.map.latitude], zoom: municipalSettings.value.map.zoom, attributionControl: { compact: true } });
    void loadMunicipalSettings().then(() => { if (!props.pin && !props.reports.length) map?.easeTo({ center: [municipalSettings.value.map.longitude, municipalSettings.value.map.latitude], zoom: municipalSettings.value.map.zoom, duration: 0 }); }).catch(() => {});
  } catch { failed.value = true; return; }
  styleTimer = setTimeout(() => { mapError.value = true; }, 15000);
  map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right'); map.scrollZoom.disable();
  map.on('click', event => { if (props.editable) emit('pick', { latitude: Number(event.lngLat.lat.toFixed(6)), longitude: Number(event.lngLat.lng.toFixed(6)) }); });
  map.on('error', () => { mapError.value = true; clearTimeout(styleTimer); });
  map.on('style.load', () => { styleReady = true; clearTimeout(styleTimer); mapError.value = false; updateBoundaryLayers(map!, true); });
  map.on('load', () => { ready = true; renderReports(); renderLocation(); fitReports(); lastReportLocations = reportSignature(); emit('zoom', map!.getZoom()); });
  map.on('zoomend', () => { emit('zoom', map!.getZoom()); if (map && styleReady) updateBoundaryLayers(map); });
  observer = new ResizeObserver(() => map?.resize()); observer.observe(container.value!);
});
watch(() => mapPreferences.value.mode, changeStyle);
watch(reducedMotion, reduce => { if (reduce) map?.stop(); });
watch(() => mapPreferences.value.boundaries, () => { if (map && styleReady) updateBoundaryLayers(map); }, { deep: true });
watch(() => props.reports, () => {
  if (!ready) return;
  renderReports(); const signature = reportSignature();
  if (signature !== lastReportLocations) { fitReports(); lastReportLocations = signature; }
}, { deep: true });
watch(() => [props.pin, props.gps, props.editable], () => { renderLocation(); if (props.pin && map && !map.getBounds().contains([props.pin.longitude, props.pin.latitude])) fitReports(); }, { deep: true });
watch(() => props.selected, id => { renderReports(); const report = props.reports.find(item => item.id === id); if (report) map?.easeTo({ center: [report.verified_longitude ?? report.selected_longitude, report.verified_latitude ?? report.selected_latitude], zoom: 15, duration: motion() }); });
onBeforeUnmount(() => { clearTimeout(styleTimer); observer?.disconnect(); markers.forEach(marker => marker.remove()); pinMarker?.remove(); gpsMarker?.remove(); map?.remove(); map = undefined; });
</script>
<template><div class="map-shell"><button v-if="editable && !failed" type="button" class="map-center-picker button secondary" @click="pickCenter">Place pin at map center</button><div ref="container" class="hazard-map" aria-label="Interactive hazard location map"></div><div v-if="styleSelector && !failed" class="map-inline-style"><label>Map style<FormSelect v-model="mapPreferences.mode" aria-label="Location map style" name="map_preferences.mode"><option v-for="mode in mapModes" :key="mode.id" :value="mode.id">{{ mode.label }}</option></FormSelect></label></div><div v-if="failed" class="map-unavailable">The interactive map needs WebGL. Try a supported browser; use a supported browser to choose a report pin. Report lists remain available.</div><div v-else-if="mapError" class="map-load-error" role="status"><p>Some map content could not load. Check your connection or try another map style.</p><button type="button" @click="changeStyle">Retry map</button><button v-if="mapPreferences.mode !== 'streets'" type="button" @click="mapPreferences.mode = 'streets'">Use Streets</button></div></div></template>

<style scoped>.map-center-picker { position: absolute; left: 12px; bottom: 32px; z-index: 3; max-width: calc(100% - 24px); white-space: normal; }</style>
