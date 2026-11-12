<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
const props = withDefaults(defineProps<{ allowRecovery?: boolean }>(), { allowRecovery: true });
const code = defineModel<string>({ required: true });
const recovery = ref(false); const digits = ref(Array<string>(6).fill('')); const inputs = ref<HTMLInputElement[]>([]);
watch(code, value => { if (!recovery.value && value !== digits.value.join('')) digits.value = Array.from({ length: 6 }, (_, i) => value[i] || ''); });
async function focus(index: number) { await nextTick(); inputs.value[index]?.focus(); inputs.value[index]?.select(); }
function input(index: number, event: Event) {
  const value = (event.target as HTMLInputElement).value.replace(/\D/g, '');
  if (value.length > 1) { pasteValue(value, index); return; }
  digits.value[index] = value; code.value = digits.value.join('');
  if (value && index < 5) void focus(index + 1);
}
function pasteValue(value: string, index = 0) {
  const text = value.replace(/\D/g, '').slice(0, 6); const start = text.length === 6 ? 0 : index;
  for (let i = 0; i < text.length && start + i < 6; i++) digits.value[start + i] = text[i]!;
  code.value = digits.value.join(''); void focus(Math.min(5, start + text.length));
}
function paste(event: ClipboardEvent) { event.preventDefault(); const index = inputs.value.indexOf(event.target as HTMLInputElement); pasteValue(event.clipboardData?.getData('text') || '', Math.max(0, index)); }
function key(index: number, event: KeyboardEvent) {
  if (event.key === 'ArrowLeft' && index > 0) { event.preventDefault(); void focus(index - 1); }
  if (event.key === 'ArrowRight' && index < 5) { event.preventDefault(); void focus(index + 1); }
  if (event.key === 'Backspace' && !digits.value[index] && index > 0) { event.preventDefault(); digits.value[index - 1] = ''; code.value = digits.value.join(''); void focus(index - 1); }
}
function toggle() { recovery.value = !recovery.value; code.value = ''; digits.value = Array<string>(6).fill(''); if (!recovery.value) void focus(0); }
</script>
<template>
  <div class="two-factor-entry">
    <label v-if="recovery">Recovery code<input v-model="code" autocomplete="off" autocapitalize="off" spellcheck="false" required maxlength="32"></label>
    <fieldset v-else><legend>2FA code</legend><p class="field-help">Enter the six-digit code from your authenticator app. You can paste the whole code.</p><div class="two-factor-digits" @paste="paste"><input v-for="(_, index) in digits" :key="index" :ref="element => { if (element) inputs[index] = element as HTMLInputElement; }" class="two-factor-digit" type="text" inputmode="numeric" :autocomplete="index === 0 ? 'one-time-code' : 'off'" :maxlength="index === 0 ? 6 : 1" pattern="[0-9]" :aria-label="`2FA code digit ${index + 1}`" :value="digits[index]" required @input="input(index, $event)" @keydown="key(index, $event)" @focus="($event.target as HTMLInputElement).select()"></div></fieldset>
    <button v-if="props.allowRecovery" type="button" class="text-button" @click="toggle">{{ recovery ? 'Use authenticator code' : 'Use a recovery code' }}</button>
  </div>
</template>
<style scoped>
.two-factor-entry { display: grid; gap: 12px; min-width: 0; }
.two-factor-entry fieldset { padding: 0; margin: 0; border: 0; min-width: 0; }
.two-factor-entry legend { font-weight: 700; }
.two-factor-entry .field-help { margin-block: 8px 16px; }
.two-factor-digits { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 8px; }
.two-factor-entry input.two-factor-digit { min-width: 0; width: 100%; min-height: 56px; text-align: center; font-size: 1.5rem; padding: 10px 4px; font-variant-numeric: tabular-nums; }
@media (max-width: 480px) { .two-factor-digits { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
:global(.largeText) .two-factor-digits { grid-template-columns: repeat(3, minmax(0, 1fr)); }
</style>
