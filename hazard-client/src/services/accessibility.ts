import { computed, reactive, readonly, ref, watch } from 'vue';

export const textSizeRange = { min: 100, max: 200, step: 5 } as const;
function validTextSize(value: unknown): value is number {
  return typeof value === 'number' && Number.isInteger(value) && value >= textSizeRange.min && value <= textSizeRange.max && value % textSizeRange.step === 0;
}
type Preferences = { textSize: number; boldText: boolean; highContrast: boolean; readingSpace: boolean; reduceMotion: boolean; keepMessages: boolean };
const defaults: Preferences = { textSize: 100, boldText: false, highContrast: false, readingSpace: false, reduceMotion: false, keepMessages: false };
const key = 'munialert-reading-v1';
let storageKey = key; let restoring = false;
const settings = reactive({ ...defaults });
export const reading = readonly(settings);
const systemMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const systemReduced = ref(systemMotion.matches);
export const reducedMotion = computed(() => systemReduced.value || settings.reduceMotion);
systemMotion.addEventListener('change', () => { systemReduced.value = systemMotion.matches; });

try {
  const stored = JSON.parse(localStorage.getItem(key) || 'null');
  if (stored && typeof stored === 'object') {
    if (validTextSize(stored.textSize)) settings.textSize = stored.textSize;
    for (const name of ['boldText', 'highContrast', 'readingSpace', 'reduceMotion', 'keepMessages'] as const) {
      if (typeof stored[name] === 'boolean') settings[name] = stored[name];
    }
  }
} catch { /* Reading controls still work when storage is unavailable. */ }

export function setReading<K extends keyof Preferences>(name: K, value: Preferences[K]) {
  if (name === 'textSize' && !validTextSize(value)) return;
  settings[name] = value;
}
export function resetReading() { Object.assign(settings, defaults); }
export function restoreReadingScope(userId: number | null, serverPreferences?: unknown) {
  storageKey = userId == null ? key : `munialert-reading-user-${userId}-v1`;
  let stored = serverPreferences;
  if (stored == null) { try { stored = JSON.parse(localStorage.getItem(storageKey) || 'null'); } catch {} }
  const next = { ...defaults };
  if (stored && typeof stored === 'object') {
    const values = stored as Record<string, unknown>;
    if (validTextSize(values.textSize)) next.textSize = values.textSize;
    for (const name of ['boldText', 'highContrast', 'readingSpace', 'reduceMotion', 'keepMessages'] as const) if (typeof values[name] === 'boolean') next[name] = values[name];
  }
  restoring = true; Object.assign(settings, next); restoring = false; apply();
  try { localStorage.setItem(storageKey, JSON.stringify(settings)); } catch {}
}
function apply() {
  const root = document.documentElement;
  root.style.setProperty('--reading-scale', String(settings.textSize / 100));
  root.dataset.textSize = String(settings.textSize);
  root.classList.toggle('largeText', settings.textSize >= 150);
  for (const name of ['boldText', 'highContrast', 'readingSpace'] as const) root.classList.toggle(name, settings[name]);
  root.classList.toggle('reduceMotion', reducedMotion.value);
  // Glass and map effects use this event as well as the operating-system preference.
  window.dispatchEvent(new Event('reading-preferences-changed'));
}
watch([settings, reducedMotion], () => {
  if (restoring) return;
  apply();
  try { localStorage.setItem(storageKey, JSON.stringify(settings)); } catch { /* Session-only preferences. */ }
}, { deep: true, flush: 'sync' });
apply();
