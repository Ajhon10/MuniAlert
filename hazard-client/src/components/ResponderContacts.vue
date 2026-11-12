<script setup lang="ts">
import { PhoneCall, Copy } from '@lucide/vue';
import HotlineDirectory from './HotlineDirectory.vue';
import { municipalSettings } from '../services/municipalSettings';
import { notify } from '../services/feedback';
import type { Hazard } from '../types';
const props = defineProps<{ report: Hazard }>();
async function copyBrief() { try { const report = props.report; await navigator.clipboard.writeText(`MuniAlert ${report.reference_number}\n${report.category.name} — ${report.barangay}\n${report.assistance_needed ? 'Reporter requests immediate assistance.\n' : ''}${report.fact_checked_at ? 'Municipal factual review recorded.' : 'Unverified reporter submission; confirm with responding agency.'}\n${report.verified_latitude != null ? 'Verified incident location' : 'Reporter-selected location'}: ${report.verified_latitude ?? report.selected_latitude}, ${report.verified_longitude ?? report.selected_longitude}`); notify('Responder brief copied. Review it before sharing with an authorized agency.'); } catch { notify('Copy unavailable. Read the reference and location to the responding agency.', 'error'); } }
</script>
<template><details class="responder-contacts" :open="report.assistance_needed"><summary><PhoneCall :size="20" aria-hidden="true" />Contact agencies & responders</summary><div class="form-body"><p>Coordinate directly with the appropriate agency. These controls do not place an automatic call or dispatch a team.</p><button type="button" class="button secondary" @click="copyBrief"><Copy :size="18" aria-hidden="true" />Copy responder brief</button><p class="field-help">The brief includes the reference, category, area and selected/verified incident coordinates. Private photos, identity and notes are excluded.</p><HotlineDirectory :hotlines="municipalSettings.hotlines" /></div></details></template>
<style scoped>.responder-contacts { border: 1px solid var(--border, #d7e3dc); border-radius: 16px; margin-block: 20px; } summary { min-height: 56px; padding: 16px; cursor: pointer; font-weight: 700; } summary svg { vertical-align: middle; margin-right: 8px; }</style>
