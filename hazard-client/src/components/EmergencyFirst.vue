<script setup lang="ts">
import { ref } from 'vue';
import { PhoneCall, ArrowRight, TriangleAlert } from '@lucide/vue';
import Modal from './Modal.vue';
import HotlineDirectory from './HotlineDirectory.vue';
import { municipalSettings } from '../services/municipalSettings';
const emit = defineEmits<{ close: []; proceed: [assistanceNeeded: boolean] }>();
const emergency = ref(false);
</script>
<template>
  <Modal :title="emergency ? 'Get immediate help' : 'Before you report'" @close="emit('close')">
    <div class="form-body emergency-first">
      <template v-if="!emergency"><TriangleAlert :size="36" aria-hidden="true" /><h3>Does someone need immediate assistance?</h3><p>For example, an accident, fire, serious injury or someone in danger.</p><p lang="fil" class="field-help">May aksidente o taong nangangailangan ng agarang tulong?</p><button type="button" class="button primary full" @click="emergency = true"><PhoneCall :size="20" aria-hidden="true" />Yes, immediate help is needed</button><button type="button" class="button secondary full" @click="emit('proceed', false)">No, continue to the report<ArrowRight :size="18" aria-hidden="true" /></button></template>
      <template v-else><p class="notice emergency">Move to safety. Call an agency if you can. Online reports do not guarantee dispatch.</p><HotlineDirectory :hotlines="municipalSettings.hotlines" /><p>No cellular load or unable to call? You can still send a report using an internet connection. Ask someone nearby to call if it is safe to do so.</p></template>
    </div>
    <template #footer><button v-if="emergency" type="button" class="button primary full" @click="emit('proceed', true)">Continue to report online<ArrowRight :size="18" aria-hidden="true" /></button><button v-else type="button" class="button secondary" @click="emit('close')">Cancel</button></template>
  </Modal>
</template>
<style scoped>.emergency-first > h3 { font-size: 1.4rem; line-height: 1.4; } .emergency-first > p { line-height: 1.7; }</style>
