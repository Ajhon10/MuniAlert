<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Hazard } from '../types';
import { newsLink, newsShareText, platformShareUrl } from '../services/newsSharing';
import { notify, useFeedback } from '../services/feedback';
import Modal from './Modal.vue';
import FormTextarea from './FormTextarea.vue';
const props = defineProps<{ report: Hazard; platform: 'facebook' | 'x' | 'whatsapp' }>();
defineEmits<{ close: [] }>();
const error = ref(''); useFeedback([error]);
const content = computed(() => newsShareText(props.report) + '\n' + newsLink(props.report));
const local = computed(() => ['localhost', '127.0.0.1', '[::1]'].includes(new URL(newsLink(props.report)).hostname));
async function copy() { try { await navigator.clipboard.writeText(content.value); notify('Post text copied. Paste it into your social post.'); return true; } catch { error.value = 'Copy is unavailable here. Select and copy the prepared text below.'; return false; } }
async function open() {
  const popup = window.open('about:blank', '_blank');
  if (popup) popup.opener = null;
  await copy();
  if (popup) popup.location.href = platformShareUrl(props.platform, props.report);
  else error.value = 'Allow pop-ups or use the Open platform link below. Your prepared text remains available.';
}
</script>
<template><Modal title="Share verified news" @close="$emit('close')"><div class="form-body"><p>Share only the municipality’s reviewed public update. Private evidence, contact details and exact coordinates are excluded.</p><p v-if="platform === 'facebook'">Facebook controls the caption in its composer. Copy this prepared post, open Facebook, then paste it. A public news link supplies the title and description preview.</p><p v-if="local" class="notice neutral">This news link is on localhost. Facebook and other people cannot reach your PC’s local website. Use a deployed public HTTPS news link for a live link preview.</p><label>Prepared post<FormTextarea :model-value="content" readonly rows="8" aria-label="Prepared share post" /></label><a :href="platformShareUrl(platform, report)" target="_blank" rel="noopener noreferrer">Open {{ platform === 'x' ? 'X' : platform === 'facebook' ? 'Facebook' : 'WhatsApp' }}</a></div><template #footer><button class="button secondary" @click="copy">Copy post text</button><button class="button primary" @click="open">Copy post & open {{ platform === 'x' ? 'X' : platform === 'facebook' ? 'Facebook' : 'WhatsApp' }}</button></template></Modal></template>
