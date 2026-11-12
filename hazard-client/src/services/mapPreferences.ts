import { ref, watch } from 'vue';
import { mapModes, type BoundaryLevel, type MapMode } from '../config/maps';
import { notify } from './feedback';

type Preferences = { mode: MapMode; boundaries: Record<BoundaryLevel, boolean> };
const key = 'munialert.map-preferences.v1';
function read(): Preferences {
  const defaults: Preferences = { mode: import.meta.env.VITE_MAP_STYLE_URL ? 'municipal' : 'clear', boundaries: { province: false, city: true, barangay: true } };
  try {
    const saved = JSON.parse(localStorage.getItem(key) || 'null');
    if (saved && mapModes.some(mode => mode.id === saved.mode)) defaults.mode = saved.mode;
    for (const level of ['province', 'city', 'barangay'] as const) if (typeof saved?.boundaries?.[level] === 'boolean') defaults.boundaries[level] = saved.boundaries[level];
  } catch { /* Map use stays available when storage is disabled or malformed. */ }
  return defaults;
}
export const mapPreferences = ref<Preferences>(read());
watch(mapPreferences, value => { try { localStorage.setItem(key, JSON.stringify(value)); } catch { /* Preferences are optional. */ } }, { deep: true });
watch(() => mapPreferences.value.mode, value => notify(`${mapModes.find(mode => mode.id === value)?.label || 'Selected'} map selected.`, 'info'));
for (const level of ['province', 'city', 'barangay'] as const) {
  const label = { province: 'Province', city: 'City and municipality', barangay: 'Barangay' }[level];
  watch(() => mapPreferences.value.boundaries[level], enabled => notify(`${label} boundaries ${enabled ? 'shown' : 'hidden'}.`, 'info'));
}
