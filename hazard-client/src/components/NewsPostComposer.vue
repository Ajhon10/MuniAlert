<script setup lang="ts">
import { computed, ref } from 'vue';
import { Newspaper, ShieldCheck } from '@lucide/vue';
import { api } from '../services/api';
import { notify, useFeedback } from '../services/feedback';
import { acceptedStatuses, type Hazard } from '../types';
import ConfirmDialog from './ConfirmDialog.vue';
import NewsIncidentDetails from './NewsIncidentDetails.vue';
const props = defineProps<{ report: Hazard }>();
const emit = defineEmits<{ updated: [report: Hazard] }>();
const title = ref(props.report.public_title || ''); const summary = ref(props.report.public_summary || ''); const reviewed = ref(false);
const includeName = ref(props.report.published_at ? !!props.report.public_reporter_name : true);
const preview = computed(() => ({ ...props.report, public_reporter_name: includeName.value ? props.report.reporter?.name : null, public_occurred_at: props.report.occurred_at }));
const pending = ref<'publish' | 'remove' | null>(null); const busy = ref(false); const error = ref('');
const eligible = computed(() => !!props.report.fact_checked_at && acceptedStatuses.includes(props.report.status));
useFeedback([error]);
async function save() {
  if (!pending.value || busy.value) return;
  busy.value = true; error.value = '';
  try {
    const removing = pending.value === 'remove';
    const result = await api<{ data: Hazard }>(`/api/admin/reports/${props.report.id}/post`, removing ? { method: 'DELETE' } : { method: 'POST', body: JSON.stringify({ public_title: title.value, public_summary: summary.value, privacy_reviewed: reviewed.value, include_reporter_name: includeName.value }) });
    emit('updated', result.data); pending.value = null; reviewed.value = false;
    if (removing) title.value = summary.value = '';
    notify(removing ? 'News post removed from the feed and its share page.' : 'Reviewed news post published to the community feed.', 'success');
  } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; }
}
</script>
<template>
  <section class="news-post-composer review-step">
    <h3><Newspaper :size="22" aria-hidden="true" />{{ report.published_at ? 'Manage community news post' : 'Create community news post' }}</h3>
    <p v-if="!eligible" class="notice neutral">Fact-check and accept this incident before creating a news post. Its original submission remains private.</p>
    <form v-else class="form-body" @submit.prevent="pending = 'publish'">
      <p class="field-help">Describe what happened, the current response and any safety advice. Check the incident details and submitter credit below. Keep contact details, injury details and exact addresses private. Original descriptions and evidence photos are never added automatically.</p>
      <label>Public title<input v-model="title" minlength="5" maxlength="120" required></label>
      <label>Public summary<textarea v-model="summary" rows="4" minlength="15" maxlength="1000" required></textarea></label>
      <label class="checkbox-label"><input v-model="includeName" type="checkbox">Include the submitter's display name in the public post.</label>
      <p class="field-help">Uncheck to withhold the name when attribution could put the person at risk. Email, account ID and contact information are never included.</p>
      <article class="post-preview" aria-label="Public post preview"><span class="post-preview-label"><ShieldCheck :size="17" aria-hidden="true" />Public preview · verified incident</span><h4>{{ title || 'Your community headline' }}</h4><p>{{ summary || 'Your reviewed community update will appear here.' }}</p><NewsIncidentDetails :report="preview" /></article>
      <label class="checkbox-label"><input v-model="reviewed" type="checkbox" required>Reviewed for public sharing, including incident details and submitter credit.</label>
      <div class="button-row"><button type="submit" class="button primary" :disabled="busy">{{ report.published_at ? 'Save news post changes' : 'Publish news post' }}</button><button v-if="report.published_at" type="button" class="button secondary" :disabled="busy" @click="pending = 'remove'">Unpublish news post</button></div>
      <p v-if="report.published_at" class="field-help">Published {{ new Date(report.published_at).toLocaleString('en-PH', { timeZone: 'Asia/Manila' }) }} PHT. Members can share the public post.</p>
    </form>

    <ConfirmDialog v-if="pending" :title="pending === 'remove' ? 'Unpublish this news post?' : 'Publish this news post?'" :message="pending === 'remove' ? 'The newsfeed and share page will stop showing this post. Copies already shared on other platforms may remain.' : 'This reviewed text will appear in the community newsfeed and on a public share page.'" :confirm-label="pending === 'remove' ? 'Unpublish post' : 'Publish post'" :danger="pending === 'remove'" :busy="busy" :error="error" @close="pending = null" @confirm="save" />
  </section>
</template>
