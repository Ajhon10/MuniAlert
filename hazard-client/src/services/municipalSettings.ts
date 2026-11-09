import { ref } from 'vue';
import { api } from './api';
import type { MunicipalSettings } from '../types';
import municipality from '../config/municipality.json';
export const municipalSettings = ref<MunicipalSettings>({ map: municipality.map, hotlines: [] });
export async function loadMunicipalSettings() { municipalSettings.value = await api<MunicipalSettings>('/api/settings'); return municipalSettings.value; }
