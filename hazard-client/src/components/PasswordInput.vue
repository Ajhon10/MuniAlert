<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, useAttrs, useId, watch } from 'vue';
import { Eye, EyeOff } from '@lucide/vue';
defineOptions({ inheritAttrs: false });
const props = defineProps<{ label: string }>();
const value = defineModel<string>({ required: true });
const attrs = useAttrs(); const generatedId = useId();
const input = ref<HTMLInputElement>(); const visible = ref(false);
watch(value, next => { if (!next) visible.value = false; });
function conceal() { if (document.hidden) visible.value = false; }
onMounted(() => document.addEventListener('visibilitychange', conceal));
onBeforeUnmount(() => document.removeEventListener('visibilitychange', conceal));
async function toggle() {
  const start = input.value?.selectionStart; const end = input.value?.selectionEnd;
  visible.value = !visible.value;
  await nextTick();
  if (start != null && end != null) input.value?.setSelectionRange(start, end);
}
</script>
<template>
  <div class="password-field">
    <label :for="String(attrs.id || generatedId)">{{ props.label }}</label>
    <div class="password-control">
      <input ref="input" class="password-input form-control-input" v-bind="attrs" :name="String(attrs.name || (label.toLowerCase().includes('confirm') ? 'password_confirmation' : label.toLowerCase().includes('current') ? 'current_password' : 'password'))" :id="String(attrs.id || generatedId)" v-model="value" :type="visible ? 'text' : 'password'" autocapitalize="off" :spellcheck="false">
      <button type="button" class="password-visibility" :aria-label="`${visible ? 'Hide' : 'Show'} ${props.label.toLowerCase()}`" :aria-pressed="visible" :aria-controls="String(attrs.id || generatedId)" :disabled="attrs.disabled !== undefined && attrs.disabled !== false" @mousedown.prevent @click="toggle">
        <component :is="visible ? EyeOff : Eye" :size="21" aria-hidden="true" />
      </button>
    </div>
  </div>
</template>
<style scoped>
.password-field { display: flex; flex-direction: column; gap: 8px; min-width: 0; }
.password-control { position: relative; min-width: 0; }
.password-field .password-control input.password-input { width: 100%; min-height: var(--control-height, 52px); border-radius: var(--control-radius, 12px); padding: var(--control-padding-block, 12px) 58px var(--control-padding-block, 12px) var(--control-padding-inline, 14px); }
.password-visibility { position: absolute; inset: 0 4px 0 auto; margin-block: auto; display: grid; place-items: center; width: 48px; height: 48px; min-height: 48px; border: 0; border-radius: 8px; background: transparent; color: #315f4b; cursor: pointer; }
.password-visibility:hover { background: #e4eee8; }
.password-visibility:focus-visible { outline: 3px solid #246d51; outline-offset: -3px; }
.password-visibility:disabled { cursor: default; opacity: .6; }
</style>
