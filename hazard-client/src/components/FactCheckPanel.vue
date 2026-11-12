<script setup lang="ts">
import { computed, ref, useId } from 'vue';
import { BadgeCheck, ClipboardCheck } from '@lucide/vue';
import { api } from '../services/api';
import { notify, useFeedback } from '../services/feedback';
import type { Hazard, Point } from '../types';
import ConfirmDialog from './ConfirmDialog.vue';
const props = defineProps<{ report: Hazard; point: Point }>();
const emit = defineEmits<{ verified: [report: Hazard] }>();
const pendingImages = computed(() => props.report.photos.some(photo => photo.photo_type !== 'resolution' && photo.authenticity?.required && (!photo.authenticity.review || photo.authenticity.review.verdict !== 'accepted_evidence' || photo.authenticity.review.version !== photo.authenticity.version)));
const noteHelpId = useId();
const method = ref(''); const note = ref(''); const confirmed = ref(false); const pending = ref(false); const busy = ref(false); const error = ref('');
const methods: Record<string, string> = { site_visit: 'On-site inspection', responder_confirmation: 'Confirmation from a municipal responder', official_record: 'Official incident record', cross_checked_evidence: 'Cross-checked photos, location and independent information' };
useFeedback([error]);
async function verify() {
  if (busy.value) return;
  busy.value = true; error.value = '';
  try {
    const result = await api<{ data: Hazard }>(`/api/admin/reports/${props.report.id}/verify`, { method: 'POST', body: JSON.stringify({ confirmed_happened: confirmed.value, method: method.value, verification_note: note.value, verified_latitude: props.point.latitude, verified_longitude: props.point.longitude }) });
    pending.value = false; emit('verified', result.data); notify('Incident fact-checked and accepted. You can now create its community news post.', 'success');
  } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; }
}
</script>
<template>
  <section class="fact-check-panel review-step">
    <template v-if="report.fact_checked_at">
      <h3><BadgeCheck :size="22" aria-hidden="true" />Incident fact-checked and accepted</h3>
      <p class="field-help">Accepted {{ new Date(report.fact_checked_at).toLocaleString('en-PH', { timeZone: 'Asia/Manila' }) }} PHT. Publishing a news post is a separate step.</p>
      <details v-if="report.fact_check_method"><summary>View internal verification record</summary><p><strong>{{ methods[report.fact_check_method] || report.fact_check_method }}</strong></p><p class="private-verification-note">{{ report.fact_check_note }}</p></details>
    </template>
    <template v-else-if="report.status !== 'rejected'">
      <h3><ClipboardCheck :size="22" aria-hidden="true" />Fact-check incident</h3>
      <p class="field-help">Check the original evidence and location details. Record how you established that the incident actually happened. The system records your review; a photo or location alone does not establish a fact.</p>
      <form class="form-body" @submit.prevent="pending = true">
        <label>Verification method<select v-model="method" aria-label="Verification method" required><option value="" disabled>Choose how the incident was checked</option><option v-for="(label, value) in methods" :key="value" :value="value">{{ label }}</option></select></label>
        <label>Internal verification notes<textarea v-model="note" aria-label="Internal verification notes" :aria-describedby="noteHelpId" rows="3" minlength="15" maxlength="1000" required placeholder="Summarize the checks and supporting information."></textarea><span :id="noteHelpId" class="field-help">Visible to authorized administrators. Keep personal information to the minimum needed.</span></label>
        <label class="checkbox-label"><input v-model="confirmed" type="checkbox" required>I verified that this incident actually happened.</label>
        <p v-if="pendingImages" class="field-help">Review and accept each incident photo above before factual verification. You can contact responders at any time.</p><button class="button primary" type="submit" :disabled="busy || pendingImages">Verify and accept incident</button>
      </form>
    </template>
    <p v-else class="field-help">Rejected reports cannot be accepted or published through this workflow.</p>

    <ConfirmDialog v-if="pending" title="Accept this verified incident?" message="Your fact-check will be recorded. The accepted hazard becomes visible in community alerts, and the reporter earns category progress. A news post still needs separate publication." confirm-label="Accept incident" :busy="busy" :error="error" @close="pending = false" @confirm="verify" />
  </section>
</template>
