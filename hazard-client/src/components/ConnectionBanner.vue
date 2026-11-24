<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { CheckCircle2, LoaderCircle, ServerOff, WifiOff } from '@lucide/vue';
import { checkConnection, checkingConnection, connectionStatus } from '../services/connection';

const props = defineProps<{ demo: boolean }>();
const recovered = ref(false);
const shell = ref<HTMLElement>();
let hideTimer: ReturnType<typeof setTimeout>;
let observer: ResizeObserver;
const visible = computed(() => !props.demo && (connectionStatus.value !== 'connected' || recovered.value));
const title = computed(() => ({ checking: 'Connecting to server…', connected: 'Connected', offline: 'Not connected to the internet', 'server-unavailable': 'Not connected to the municipal server' }[connectionStatus.value]));
const detail = computed(() => ({ checking: 'Checking the municipal server.', connected: 'You’re connected to the municipal server.', offline: 'Connect to Wi-Fi or mobile data. We’ll reconnect automatically.', 'server-unavailable': 'Check your connection; the server may be unavailable. Retrying automatically.' }[connectionStatus.value]));
watch([connectionStatus, () => props.demo], ([status, demo], [previous, wasDemo]) => {
  clearTimeout(hideTimer);
  recovered.value = !demo && !wasDemo && status === 'connected' && previous !== 'connected';
  if (recovered.value) hideTimer = setTimeout(() => { recovered.value = false; }, 3000);
});
onMounted(() => {
  observer = new ResizeObserver(() => document.documentElement.style.setProperty('--connection-banner-height', `${shell.value?.getBoundingClientRect().height || 0}px`));
  if (shell.value) observer.observe(shell.value);
});
onBeforeUnmount(() => { clearTimeout(hideTimer); observer?.disconnect(); document.documentElement.style.removeProperty('--connection-banner-height'); });
</script>

<template>
  <div ref="shell" class="connection-banner-shell" :class="{ visible }">
    <div class="connection-banner-clip">
      <div class="connection-banner" :class="connectionStatus" :inert="!visible">
        <component :is="checkingConnection ? LoaderCircle : connectionStatus === 'connected' ? CheckCircle2 : connectionStatus === 'offline' ? WifiOff : connectionStatus === 'checking' ? LoaderCircle : ServerOff" :size="21" :class="{ 'connection-spinner': checkingConnection }" aria-hidden="true" />
        <div class="connection-banner-copy" role="status" aria-live="polite" aria-atomic="true"><template v-if="visible"><strong>{{ title }}</strong><span>{{ detail }}</span></template></div>
        <button v-if="connectionStatus === 'server-unavailable'" type="button" :disabled="checkingConnection" @click="checkConnection">{{ checkingConnection ? 'Retrying…' : 'Retry connection' }}</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.connection-banner-shell { position: sticky; top: 0; z-index: 100; display: grid; grid-template-rows: 0fr; transition: grid-template-rows 240ms ease; }
.connection-banner-shell.visible { grid-template-rows: 1fr; }
.connection-banner-clip { min-height: 0; overflow: hidden; }
.connection-banner { display: flex; align-items: center; justify-content: center; gap: 12px; padding: calc(10px + env(safe-area-inset-top, 0px)) max(16px, env(safe-area-inset-right, 0px)) 10px max(16px, env(safe-area-inset-left, 0px)); background: #fff0dc; color: #71400e; border-bottom: 1px solid #d8b782; font-size: .875rem; line-height: 1.5; opacity: 0; transform: translateY(-8px); transition: background-color 200ms ease, color 200ms ease, opacity 200ms ease, transform 240ms ease; }
.visible .connection-banner { opacity: 1; transform: translateY(0); }
.connection-banner.connected { background: #e2f3e9; color: #20583e; border-color: #a4cbb4; }
.connection-banner.checking { background: #eaf1f6; color: #304e65; border-color: #b6c9d7; }
.connection-banner > svg { flex-shrink: 0; }
.connection-banner-copy { display: flex; align-items: baseline; flex-wrap: wrap; gap: 2px 12px; min-width: 0; overflow-wrap: anywhere; }
.connection-banner-copy strong { font-weight: 750; }
.connection-banner button { flex-shrink: 0; min-height: 44px; padding: 6px 12px; border: 1px solid currentColor; border-radius: 8px; font-size: inherit; font-weight: 650; }
.connection-banner button:disabled { opacity: .7; cursor: wait; }
.connection-spinner { animation: connection-spin 1s linear infinite; }
@keyframes connection-spin { to { transform: rotate(360deg); } }
@media (max-width: 639px) { .connection-banner { gap: 10px; flex-wrap: wrap; justify-content: flex-start; } .connection-banner-copy { flex: 1; flex-basis: calc(100% - 36px); flex-direction: column; } .connection-banner button { margin-left: 31px; } }
@media (prefers-reduced-motion: reduce) { .connection-banner-shell, .connection-banner { transition: none; } .connection-spinner { animation: none; } }
</style>
