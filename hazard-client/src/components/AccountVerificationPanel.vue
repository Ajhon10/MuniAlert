<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, useId } from 'vue';
import AppForm from './AppForm.vue';
import FormInput from './FormInput.vue';
import FormSelect from './FormSelect.vue';
import PolicyNotice from './PolicyNotice.vue';
import { api } from '../services/api';
import { compressPhoto } from '../services/mediaService';
import { notify, useFeedback } from '../services/feedback';
import { identityLabels, identityReasons, loadVerification, type Verification } from '../services/verification';
const props = defineProps<{ user?: import('../types').User }>();
const emit = defineEmits<{ updated: [accountId: number, ready: boolean, verified: boolean] }>();
let active = true; onBeforeUnmount(() => { active = false; });
const state = ref<Verification>(); const busy = ref(false); const error = ref(''); const consent = ref(false); const accepted = ref(false); const version = ref(''); const documentType = ref('philsys'); const image = ref<File>(); const fileKey = ref(0); const formId = useId();
useFeedback([error]);
async function load() { error.value = ''; try { const loaded = await loadVerification(); if (active) { state.value = loaded; if (props.user) emit('updated', props.user.id, loaded.account_ready, loaded.identity_verified); } } catch (e) { error.value = (e as Error).message; } }
onMounted(load);
async function act(action: () => Promise<unknown>) { busy.value = true; error.value = ''; try { await action(); await load(); } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; } }
async function acceptPolicies() { await act(async () => { await api('/api/account/policies', { method: 'POST', body: JSON.stringify({ accepted: accepted.value, version: version.value }) }); notify('Current policies accepted.'); }); }
async function sendEmail() { await act(async () => { const result = await api<{message: string}>('/api/security/verify-email', { method: 'POST' }); notify(result.message, 'info'); }); }
async function upload() { if (!image.value) { error.value = 'Choose one government-ID image.'; return; } await act(async () => { const data = new FormData(); data.append('document_type', documentType.value); data.append('version', version.value); data.append('consent', consent.value ? '1' : '0'); data.append('image', await compressPhoto(image.value!)); await api('/api/account/identity', { method: 'POST', body: data }); image.value = undefined; fileKey.value++; consent.value = false; notify('ID submitted privately for administrator review.'); }); }
async function withdraw() { await act(async () => { await api('/api/account/identity', { method: 'DELETE' }); notify('Identity submission withdrawn. Any stored image has been deleted.'); }); }
</script>
<template>
  <section class="cms-card verification-panel" :aria-label="user?.role === 'admin' ? 'Administrator verification' : 'Reporter verification'"><h2>{{ user?.role === 'admin' ? 'Administrator verification' : 'Reporter verification' }}</h2><p>Verify your email and accept the current policies to use your account. Government-ID verification is optional for both administrators and community members. You can report without an ID once your email is verified and policies are accepted.</p>
    <PolicyNotice @loaded="version = $event" />
    <template v-if="state">
      <p role="status"><strong>{{ state.account_ready ? (user?.role === 'admin' ? 'Your administrator account is ready.' : 'You are ready to report.') : 'Verify your email and accept the current policies.' }}</strong></p>
      <p v-if="user?.role === 'admin'" class="field-help">Administrator access also requires authenticator MFA. A different authorized administrator reviews your optional ID. You cannot approve your own ID.</p><h3>1. Terms and privacy</h3><p v-if="state.policies_accepted">Current policies accepted.</p><AppForm v-else @submit.prevent="acceptPolicies"><label class="checkbox-label"><FormInput v-model="accepted" type="checkbox" name="accepted" required />I agree to the terms and acknowledge the privacy notice.</label><button class="button secondary" :disabled="busy || !version">Accept current policies</button></AppForm>
      <h3>2. Email address</h3><p>{{ state.email_verified ? 'Email verified.' : 'Your email is not verified. Open the verification link in your email, then refresh your verification status.' }}</p><button v-if="!state.email_verified && state.mail_available" class="button secondary" :disabled="busy" @click="sendEmail">Send verification email</button><p v-if="!state.mail_available" class="field-help">Email delivery is waiting for MuniAlert’s separate SMTP credentials and scheduled mail worker.</p>
      <h3>Optional: verified identity badge</h3><p>Build trust in the community by verifying one Philippine government ID. Once approved, a blue check badge appears beside your profile name. The badge confirms a manual identity review; it does not certify the accuracy of a report. Skipping, withdrawing or awaiting review will not block reporting.</p><p role="status">{{ identityLabels[state.identity_status] || state.identity_status }}</p><p v-if="state.identity_reason">{{ identityReasons[state.identity_reason] }}</p><p>Use your own ID. Your account name must match it. Keep the name, photograph, document type and issuing authority visible. Cover the ID number, QR/barcode, signature, birth date and address before selecting the image.</p><p class="field-help">Choose one redacted JPEG, PNG or WebP up to 20 MB. It is resized on this device to at most 1 MB and 1920 pixels per side before upload. Images are private, encrypted and deleted after review or expiry (seven days). This is a manual review, not government registry verification.</p>
      <p v-if="!state.identity_available" class="notice neutral">ID collection is waiting for the operator’s public privacy contact. Do not send an ID by email.</p>
      <AppForm v-else-if="state.email_verified && state.policies_accepted && !['pending', 'approved'].includes(state.identity_status)" :id="formId" @submit.prevent="upload"><label>ID type<FormSelect v-model="documentType" name="document_type"><option v-for="(label, value) in state.document_types" :key="value" :value="value">{{ label }}</option></FormSelect></label><label>One redacted government-ID image<FormInput :key="fileKey" type="file" name="image" accept="image/jpeg,image/png,image/webp" required @change="image = ($event.target as HTMLInputElement).files?.[0]" /></label><label class="checkbox-label"><FormInput v-model="consent" type="checkbox" name="consent" required />I consent to the operator processing this ID image for manual identity verification as described in the privacy notice.</label><button class="button primary" :disabled="busy || !version">{{ busy ? 'Submitting…' : 'Submit ID for private review' }}</button></AppForm>
      <button v-if="['pending', 'approved', 'name_changed'].includes(state.identity_status)" class="button secondary" :disabled="busy" @click="withdraw">Withdraw identity verification</button>
    </template><p v-else role="status">{{ error || 'Loading verification status…' }}</p>
    <button class="button secondary" :disabled="busy" @click="load">Refresh verification status</button>
  </section>
</template>
<style scoped>
.verification-panel { display: grid; gap: 16px; padding: 24px; min-width: 0; overflow-wrap: anywhere; }
.verification-panel form { display: grid; gap: 16px; }
.verification-panel .button { justify-self: start; white-space: normal; }
.verification-panel p { line-height: 1.65; }
.verification-panel label { display: grid; gap: 8px; font-size: 1rem; line-height: 1.65; }
.verification-panel label.checkbox-label { display: flex; align-items: flex-start; gap: 12px; }
.verification-panel .checkbox-label input { flex: 0 0 24px; width: 24px; height: 24px; margin-top: 3px; }
.verification-panel input[type=file] { max-width: 100%; min-height: 44px; font: inherit; }
</style>
