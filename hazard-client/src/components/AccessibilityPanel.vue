<script setup lang="ts">
import { Minus, Plus } from '@lucide/vue';
import { ref, watch } from 'vue';
import { reading, setReading, resetReading, textSizeRange } from '../services/accessibility';
import { notify } from '../services/feedback';
import { readingAccount, flushReadingSave } from '../services/readingAccount';
import Modal from './Modal.vue';
defineEmits<{ close: [] }>();
const options = [
  { key: 'boldText', label: 'Bold text', help: 'Make letters heavier and easier to see.' },
  { key: 'highContrast', label: 'High contrast', help: 'Use darker text, clear borders and solid surfaces.' },
  { key: 'readingSpace', label: 'More reading space', help: 'Add space between letters and lines.' },
  { key: 'reduceMotion', label: 'Reduce motion', help: 'Show numbers immediately and stop decorative movement.' },
  { key: 'keepMessages', label: 'Keep messages visible', help: 'Leave action messages on screen until you dismiss them.' },
] as const;
function toggle(key: typeof options[number]['key'], value: boolean) { setReading(key, value); }
function reset() { resetReading(); notify('Reading settings reset to default.'); }
const draftSize = ref(reading.textSize);
watch(() => reading.textSize, value => draftSize.value = value);
function resize(event: Event) { draftSize.value = Number((event.target as HTMLInputElement).value); }
</script>
<template>
  <Modal title="Reading & accessibility" @close="$emit('close')">
    <div class="reading-settings">
      <p>Make MuniAlert comfortable to read. Preview text size below. {{ readingAccount.signedIn ? 'Your custom settings are saved to your account and restored after sign-in.' : 'Guest preferences are saved on this device. Sign in to sync your own settings.' }}</p>
      <fieldset class="reading-size">
        <legend>Text size</legend>
        <div class="reading-size-summary"><p id="reading-size-help">Slide to preview. Release to apply.</p><output for="reading-text-size" aria-hidden="true">{{ draftSize }}%</output></div>
        <div class="reading-size-controls">
          <button type="button" class="reading-size-step" aria-label="Decrease text size" :disabled="reading.textSize <= textSizeRange.min" @click="setReading('textSize', reading.textSize - textSizeRange.step)"><Minus :size="21" aria-hidden="true" /></button>
          <input id="reading-text-size" type="range" aria-label="Text size" aria-describedby="reading-size-help" :aria-valuetext="`${draftSize}% text size`" :min="textSizeRange.min" :max="textSizeRange.max" :step="textSizeRange.step" :value="draftSize" :style="{ '--range-progress': `${draftSize - textSizeRange.min}%` }" @input="resize" @change="setReading('textSize', draftSize)">
          <button type="button" class="reading-size-step" aria-label="Increase text size" :disabled="reading.textSize >= textSizeRange.max" @click="setReading('textSize', reading.textSize + textSizeRange.step)"><Plus :size="21" aria-hidden="true" /></button>
        </div>
        <div class="reading-size-limits" aria-hidden="true"><span>100%</span><span>200%</span></div>
      </fieldset>
      <div class="reading-preview" :style="{ fontSize: `${draftSize / reading.textSize}rem` }"><strong>Community safety starts with you.</strong><p>Report a hazard, check its progress and read updates from your municipality.</p><span>Preview · {{ draftSize }}% text size</span></div>
      <label v-for="option in options" :key="option.key" class="reading-option"><span><strong>{{ option.label }}</strong><small>{{ option.help }}</small></span><input type="checkbox" role="switch" :aria-label="option.label" :checked="reading[option.key]" @change="toggle(option.key, ($event.target as HTMLInputElement).checked)"></label>
      <p class="field-help">Your phone or browser can also enlarge this screen. Your device’s reduced-motion preference is always respected. Errors stay visible until dismissed.</p>
    </div>
    <template #footer><div class="modal-actions"><button class="button secondary" @click="reset">Reset reading settings</button><button class="button green" @click="flushReadingSave(); $emit('close')">Done</button></div></template>
  </Modal>
</template>
