<script setup lang="ts">
import FormInput from './FormInput.vue';
import { ref } from 'vue';
import Modal from './Modal.vue';
import ConfirmDialog from './ConfirmDialog.vue';
import { recoveryCodes } from '../services/accountSecurity';
const saved = ref(false);
const confirmClose = ref(false);
</script>
<template><Modal title="Save your recovery codes" @close="saved ? recoveryCodes = [] : confirmClose = true"><div class="form-body security-form"><p>Each code can be used once to reset a forgotten password or, with 2FA enabled, if your authenticator is unavailable. Save them somewhere private, outside this device. These codes will not be displayed again.</p><ul class="recovery-code-list"><li v-for="code in recoveryCodes" :key="code"><code>{{ code }}</code></li></ul><label class="checkbox-label"><FormInput v-model="saved" type="checkbox" name="saved" />I saved these codes in a private place.</label></div><template #footer><button class="button green" :disabled="!saved" @click="recoveryCodes = []">Continue</button></template></Modal><ConfirmDialog v-if="confirmClose" title="Close recovery codes?" message="These codes will not be shown again. You can replace them from Account security after verifying your identity." confirm-label="Close without saving" @close="confirmClose = false" @confirm="recoveryCodes = []" /></template>
