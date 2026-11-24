<script setup lang="ts">
import { onMounted, ref, useId } from 'vue';
import AppForm from './AppForm.vue';
import FormInput from './FormInput.vue';
import PasswordInput from './PasswordInput.vue';
import DataTable from './DataTable.vue';
import ConfirmDialog from './ConfirmDialog.vue';
import VerifiedIdentityBadge from './VerifiedIdentityBadge.vue';
import { api } from '../services/api';
import { notify, useFeedback } from '../services/feedback';
type Administrator = { id: number; name: string; email: string; is_active: boolean; mfa_enabled: boolean; identity_verified: boolean };
defineProps<{ accountId: number }>();
const rows = ref<Administrator[]>([]); const page = ref(1); const lastPage = ref(1);
const name = ref(''); const email = ref(''); const password = ref(''); const busy = ref(false); const error = ref(''); const selected = ref<Administrator>(); const formId = useId();
useFeedback([error]);
async function load() { try { const result = await api<{ data: Administrator[]; last_page: number }>(`/api/admin/accounts?page=${page.value}`); rows.value = result.data; lastPage.value = result.last_page; } catch (e) { error.value = (e as Error).message; } }
onMounted(load);
async function create() { if (busy.value) return; busy.value = true; error.value = ''; try { const result = await api<{message: string}>('/api/admin/accounts', { method: 'POST', body: JSON.stringify({ name: name.value, email: email.value, password: password.value }) }); password.value = ''; name.value = ''; email.value = ''; notify(result.message, 'info'); page.value = 1; await load(); } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; } }
async function enroll(row: Administrator) { busy.value = true; error.value = ''; try { const result = await api<{message: string}>(`/api/admin/accounts/${row.id}/enrollment`, { method: 'POST' }); notify(result.message, 'info'); } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; } }
async function remove() { if (!selected.value || busy.value) return; busy.value = true; error.value = ''; try { const result = await api<{message: string}>(`/api/admin/accounts/${selected.value.id}`, { method: 'DELETE' }); selected.value = undefined; notify(result.message); page.value = 1; await load(); } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; } }
</script>
<template>
  <section class="cms-card admin-accounts" aria-label="Administrator accounts"><h2>Administrator accounts</h2><p>Create a replacement administrator, have them complete MFA, email verification and policy acceptance, then sign in as that person to remove the starter account. ID verification is optional.</p>
    <AppForm :id="formId" class="form-body" @submit.prevent="create"><h3>Create administrator</h3><label>Administrator name<FormInput v-model="name" name="name" required minlength="2" maxlength="80" autocomplete="off" /></label><label>Administrator email<FormInput v-model="email" name="email" type="email" required maxlength="255" autocomplete="off" /></label><PasswordInput v-model="password" label="Initial password" autocomplete="new-password" required minlength="12" /><p class="field-help">Share the initial password privately. The new administrator must enroll an authenticator within 15 minutes; you can authorize a fresh setup window below.</p><button class="button primary" type="submit" :disabled="busy">Create administrator</button></AppForm>
    <DataTable :rows="rows" :columns="[{label: 'Administrator'}, {label: 'Setup'}, {label: 'Actions'}]"><template #row="{ row }"><td><strong class="admin-name">{{ row.name }} <VerifiedIdentityBadge v-if="row.identity_verified" /></strong><p>{{ row.email }}</p></td><td>{{ row.is_active ? (row.mfa_enabled ? 'MFA enabled' : 'MFA setup pending') : 'Inactive' }}</td><td><div class="admin-actions"><button v-if="row.is_active && !row.mfa_enabled" class="button secondary" :disabled="busy" @click="enroll(row)">Allow MFA setup</button><button v-if="row.id !== accountId" class="button secondary" :disabled="busy" @click="selected = row; error = ''">Delete {{ row.name }}</button><span v-else>Current account</span></div></td></template></DataTable>
    <p v-if="!rows.length" role="status">No administrator accounts loaded.</p><div v-if="lastPage > 1" class="pagination"><button class="button secondary" :disabled="page <= 1 || busy" @click="page--; load()">Previous</button><span>{{ page }} / {{ lastPage }}</span><button class="button secondary" :disabled="page >= lastPage || busy" @click="page++; load()">Next</button></div>
  </section><ConfirmDialog v-if="selected" title="Delete administrator?" :message="`Remove sign-in access for ${selected.name}? Municipal action history will be retained. You cannot delete your current account or the last active administrator.`" danger confirm-label="Delete administrator" :busy="busy" :error="error" @confirm="remove" @close="selected = undefined" />
</template>
<style scoped>
.admin-accounts { display: grid; gap: 20px; padding: 24px; min-width: 0; overflow-wrap: anywhere; }
.admin-accounts p { line-height: 1.65; }.admin-name,.admin-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }.admin-accounts .button { white-space: normal; }.admin-accounts td { vertical-align: top; }
</style>
