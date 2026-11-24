<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { api } from '../services/api';
import type { Policies } from '../services/verification';
const emit = defineEmits<{ loaded: [version: string] }>();
const policy = ref<Policies>(); const error = ref('');
async function load() { error.value = ''; try { policy.value = await api<Policies>('/api/policies'); emit('loaded', policy.value.version); } catch (e) { error.value = (e as Error).message; } }
onMounted(load);
</script>
<template>
  <section class="policy-notice" aria-label="Terms and privacy">
    <template v-if="policy"><p class="field-help">Operator: {{ policy.operator || 'Awaiting operator configuration' }} · Policy version {{ policy.version }}</p>
      <details><summary>Terms and conditions</summary><p v-for="text in policy.terms" :key="text">{{ text }}</p></details>
      <details><summary>Privacy notice</summary><p>Privacy requests: {{ policy.contact || 'Public privacy contact is not configured. Government-ID collection is unavailable until it is supplied.' }}</p><p v-for="text in policy.privacy" :key="text">{{ text }}</p><a href="https://privacy.gov.ph/the-right-to-be-informed/" target="_blank" rel="noopener noreferrer">Philippine National Privacy Commission: your right to be informed</a></details>
    </template><p v-else role="status">{{ error || 'Loading terms and privacy notice…' }}</p><button v-if="error" type="button" class="button secondary" @click="load">Retry loading policies</button>
  </section>
</template>
<style scoped>
.policy-notice { display: grid; gap: 12px; min-width: 0; overflow-wrap: anywhere; }
summary { cursor: pointer; font-weight: 700; padding-block: 10px; }
details p { margin-block: 12px; line-height: 1.65; }
</style>
