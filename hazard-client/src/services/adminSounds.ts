import { reactive, readonly } from 'vue';
import { notify } from './feedback';
export type AlertTone = 'auto' | 'chime' | 'pulse' | 'urgent';
const tones: AlertTone[] = ['auto', 'chime', 'pulse', 'urgent'];
const state = reactive({ enabled: false, ready: false, volume: 45, patterns: {} as Record<string, AlertTone> });
export const adminSounds = readonly(state);
let owner: number | null = null; let context: AudioContext | null = null; let generation = 0; let until = 0;
const oscillators = new Set<OscillatorNode>();
function save() { if (owner == null) return; try { localStorage.setItem(`munialert-alert-sound-${owner}`, JSON.stringify({ enabled: state.enabled, volume: state.volume, patterns: state.patterns })); } catch {} }
export function bindAdminSounds(id: number | null) {
  generation++; for (const node of oscillators) { try { node.stop(); } catch {} } oscillators.clear(); until = 0;
  owner = id; state.enabled = false; state.ready = context?.state === 'running'; state.volume = 45; state.patterns = {};
  if (id == null) return;
  try { const stored = JSON.parse(localStorage.getItem(`munialert-alert-sound-${id}`) || 'null'); if (typeof stored?.enabled === 'boolean') state.enabled = stored.enabled; if (Number.isInteger(stored?.volume) && stored.volume >= 0 && stored.volume <= 100) state.volume = stored.volume; if (stored?.patterns && typeof stored.patterns === 'object') for (const [key, value] of Object.entries(stored.patterns)) if (tones.includes(value as AlertTone)) state.patterns[key] = value as AlertTone; } catch {}
}
export async function enableAdminSounds() {
  if (owner == null) return;
  const version = generation;
  try { context ||= new AudioContext(); await context.resume(); if (version !== generation) return; state.ready = context.state === 'running'; state.enabled = state.ready; save(); if (!state.ready) notify('Sound is paused by your browser. Try Enable sound again.', 'info'); }
  catch { if (version === generation) { state.ready = false; notify('Sound is unavailable on this device. Report alerts remain in Notifications.', 'info'); } }
}
export function muteAdminSounds() { state.enabled = false; save(); for (const node of oscillators) { try { node.stop(); } catch {} } oscillators.clear(); until = 0; }
export function setAlertVolume(value: number) { if (Number.isInteger(value) && value >= 0 && value <= 100) { state.volume = value; if (value === 0) { for (const node of oscillators) { try { node.stop(); } catch {} } oscillators.clear(); until = 0; } save(); } }
export function setAlertTone(category: string, tone: AlertTone) { if (tones.includes(tone)) { state.patterns[category] = tone; save(); } }
export function playHazardAlert(category: string, emergency = false) {
  if (!state.enabled || !context || context.state !== 'running' || state.volume === 0) { state.ready = context?.state === 'running'; return; }
  const names = ['fire', 'accident', 'flood', 'tree', 'road', 'vegetation', 'streetlight', 'electrical', 'waste', 'other'];
  const index = Math.max(0, names.indexOf(category)); const base = 360 + index * 35;
  const chosen = state.patterns[category] || 'auto'; const tone = chosen === 'auto' ? (emergency ? 'urgent' : 'chime') : chosen;
  const notes = tone === 'urgent' ? [base, base * 1.25, base, base * 1.5] : tone === 'pulse' ? [base, base, base] : [base, base * 1.25, base * 1.5];
  const start = Math.max(context.currentTime + .02, until); if (start - context.currentTime > 12) return;
  notes.forEach((frequency, i) => {
    const oscillator = context!.createOscillator(); const gain = context!.createGain(); const time = start + i * .19;
    oscillator.type = 'sine'; oscillator.frequency.value = frequency; gain.gain.setValueAtTime(0, time); gain.gain.linearRampToValueAtTime(state.volume / 100 * .14, time + .015); gain.gain.exponentialRampToValueAtTime(.001, time + .16);
    oscillator.connect(gain); gain.connect(context!.destination); oscillator.onended = () => { oscillators.delete(oscillator); oscillator.disconnect(); gain.disconnect(); }; oscillators.add(oscillator); oscillator.start(time); oscillator.stop(time + .18);
  }); until = start + notes.length * .19 + .3;
}
