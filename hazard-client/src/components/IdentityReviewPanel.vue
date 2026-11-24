<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AppForm from './AppForm.vue';
import FormInput from './FormInput.vue';
import FormSelect from './FormSelect.vue';
import DataTable from './DataTable.vue';
import Modal from './Modal.vue';
import { api } from '../services/api';
import { identityImage } from '../services/verification';
import { notify, useFeedback } from '../services/feedback';
type Submission = { id: string; applicant_name: string; document_type: string; created_at: string; expires_at: string };
const rows = ref<Submission[]>([]); const page = ref(1); const lastPage = ref(1); const selected = ref<Submission>(); const url = ref(''); const checked = ref(false); const decision = ref('approved'); const reason = ref('unreadable'); const busy = ref(false); const error = ref(''); let generation = 0;
useFeedback([error]);
function close() { generation++; if (url.value) URL.revokeObjectURL(url.value); url.value = ''; selected.value = undefined; checked.value = false; }
onBeforeUnmount(close);
async function load() { error.value = ''; try { const result = await api<{data: Submission[]; last_page: number}>(`/api/admin/identities?page=${page.value}`); rows.value = result.data; lastPage.value = result.last_page; } catch (e) { error.value = (e as Error).message; } }
onMounted(load);
async function open(row: Submission) { close(); selected.value = row; decision.value = 'approved'; const current = generation; busy.value = true; error.value = ''; try { const blob = await identityImage(row.id); if (current === generation) url.value = URL.createObjectURL(blob); } catch (e) { if (current === generation) error.value = (e as Error).message; } finally { if (current === generation) busy.value = false; } }
async function review() { if (!selected.value || !url.value) return; busy.value = true; error.value = ''; try { const result = await api<{message: string}>(`/api/admin/identities/${selected.value.id}/review`, { method: 'POST', body: JSON.stringify({ decision: decision.value, checked: checked.value, reason: decision.value === 'rejected' ? reason.value : null }) }); notify(result.message); close(); await load(); } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; } }
</script>
<template>
  <section class="cms-card identity-review"><h2>Government-ID review</h2><p>Review one private ID against the applicant’s account name. Never copy, download or share this image. Approval records a manual identity check; it does not prove a hazard report is true.</p><button class="button secondary" @click="load">Refresh ID review queue</button><p v-if="!rows.length">No pending government IDs.</p><DataTable v-else :rows="rows" :columns="[{label: 'Applicant'}, {label: 'ID type'}, {label: 'Action'}]"><template #row="{row}"><td>{{ row.applicant_name }}</td><td>{{ row.document_type.replaceAll('_', ' ') }}</td><td><button class="button secondary" @click="open(row)">Review ID</button></td></template></DataTable><div v-if="lastPage > 1" class="modal-actions"><button class="button secondary" :disabled="page <= 1" @click="page--; load()">Previous</button><span>Page {{ page }} of {{ lastPage }}</span><button class="button secondary" :disabled="page >= lastPage" @click="page++; load()">Next</button></div>
    <Modal v-if="selected" title="Private government-ID review" @close="close"><div class="form-body"><h3>{{ selected.applicant_name }}</h3><p>The name and photograph must be readable; check the issuer/document type and apparent alterations. Reject uncertain documents and offer an operator review. Do not transcribe private ID numbers.</p><p v-if="busy && !url" role="status">Loading private image…</p><img v-if="url" :src="url" alt="Applicant government ID for private manual review" class="identity-preview" /><AppForm v-if="url" class="security-form" @submit.prevent="review"><label>Decision<FormSelect v-model="decision" name="decision"><option value="approved">Approve identity</option><option value="rejected">Request resubmission</option></FormSelect></label><label v-if="decision === 'rejected'">Reason<FormSelect v-model="reason" name="reason"><option value="unreadable">Unreadable image</option><option value="name_mismatch">Name does not match</option><option value="unsupported_document">Unsupported document</option><option value="cannot_verify">Unable to verify — contact operator</option></FormSelect></label><label class="checkbox-label"><FormInput v-model="checked" type="checkbox" name="checked" required />I reviewed the document and account name. Saving deletes this ID image.</label><button class="button primary" :disabled="busy">Save identity decision</button></AppForm><button v-if="!url && !busy" class="button secondary" @click="open(selected)">Retry private image</button></div></Modal>
  </section>
</template>
<style scoped>
.identity-review { display: grid; gap: 16px; padding: 24px; min-width: 0; }
.identity-preview { display: block; width: 100%; max-height: 420px; object-fit: contain; margin-block: 16px; }
</style>
