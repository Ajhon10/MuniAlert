<script setup lang="ts">
import AppForm from './AppForm.vue';
import FormInput from './FormInput.vue';
import PasswordInput from './PasswordInput.vue';
import { notify, useFeedback } from '../services/feedback';
import { onMounted, ref } from 'vue';
import { api } from '../services/api';
import type { Profile, User } from '../types';
import ConfirmDialog from './ConfirmDialog.vue';
import HazardAchievements from './HazardAchievements.vue';
import VerifiedIdentityBadge from './VerifiedIdentityBadge.vue';
const props = defineProps<{ user: User }>(); const emit = defineEmits<{ updated: [user: User] }>();
const profile = ref<Profile>(); const name = ref(props.user.name); const email = ref(props.user.email); const currentPassword = ref(''); const password = ref(''); const passwordConfirmation = ref(''); const confirm = ref(false); const busy = ref(false); const error = ref(''); const saved = ref('');
useFeedback([error], [saved]);
onMounted(async () => { try { profile.value = await api<Profile>('/api/profile'); } catch (e) { error.value = (e as Error).message; } });
async function save() { if (busy.value) return; saved.value = ''; busy.value = true; error.value = ''; try { profile.value = await api<Profile>('/api/profile', { method: 'PATCH', body: JSON.stringify({ name: name.value, email: email.value, ...(password.value ? { current_password: currentPassword.value, password: password.value, password_confirmation: passwordConfirmation.value } : {}) }) }); emit('updated', profile.value.user); password.value = currentPassword.value = passwordConfirmation.value = ''; saved.value = 'Profile updated.'; confirm.value = false; } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; } }
</script>
<template><div class="cms-grid"><section class="cms-card"><h2>Your profile</h2><p class="profile-identity"><strong>{{ profile?.user.name || user.name }}</strong> <VerifiedIdentityBadge v-if="(profile?.user || user).identity_verified" /></p><p class="field-help">{{ (profile?.user || user).identity_verified ? 'Your government ID has been reviewed. This badge does not certify reports as true.' : 'ID verification is optional. Visit Settings to earn a verified identity badge; you can report without it.' }}</p><AppForm class="form-body" @submit.prevent="confirm = true"><label>Name<FormInput v-model="name" required minlength="2" maxlength="120" name="name" /></label><label>Email<FormInput v-model="email" type="email" required name="email" /></label><template v-if="user.has_password !== false"><h3>Change password (optional)</h3><PasswordInput label="Current password" v-model="currentPassword" autocomplete="current-password" :required="!!password" /><PasswordInput label="New password" v-model="password" autocomplete="new-password" minlength="12" /><PasswordInput label="Confirm new password" v-model="passwordConfirmation" autocomplete="new-password" :required="!!password" /></template><p v-else class="field-help">Use email recovery or a saved recovery code to set a password for this account.</p><button class="button primary" type="submit">Save profile</button></AppForm></section><section class="cms-card"><h2>Achievements</h2><p class="field-help">{{ profile?.verified_reports ?? 0 }} verified reports credited to this account. A report counts once after municipal verification.</p><div v-for="badge in profile?.badges || []" :key="badge.id" class="achievement-row" :class="{ locked: !badge.earned }"><span class="achievement-badge" :class="badge.color">{{ badge.name }}</span><div><strong>{{ badge.threshold.toLocaleString() }} verified reports</strong><p>{{ badge.description }}</p><small v-if="badge.reward_type === 'cash'">Cash bonus: ₱{{ Number(badge.cash_amount).toLocaleString('en-PH') }} · municipality fulfillment</small><small v-if="badge.reward_type === 'gift'">Gift: {{ badge.reward_description }} · municipality fulfillment</small><small v-if="!badge.earned">Locked</small><small v-else>Earned</small></div></div></section></div><HazardAchievements :tracks="profile?.hazard_achievements || []" /><ConfirmDialog v-if="confirm" title="Save profile changes?" message="Your name and email will be updated for this account. If you entered a new password, you will use it on your next login." :busy="busy" :error="error" confirm-label="Save profile" @close="confirm = false" @confirm="save" /></template>

<style scoped>
.profile-identity { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; min-width: 0; }
.profile-identity strong { min-width: 0; max-width: 100%; overflow-wrap: anywhere; }
</style>
