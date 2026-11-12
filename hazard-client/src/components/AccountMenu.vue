<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { LogOut, Settings, UserRound } from '@lucide/vue';
const props = defineProps<{ initial: string; name?: string; admin: boolean }>();
const emit = defineEmits<{ profile: []; settings: []; logout: []; signIn: [] }>();
const trigger = ref<HTMLButtonElement>(); const menu = ref<HTMLElement>(); const open = ref(false);
const position = ref({ top: '0px', right: '12px' });
const identity = computed(() => props.name || 'Community guest');
function close(restore = false) { open.value = false; if (restore) trigger.value?.focus(); }
async function toggle() {
  if (open.value) { close(true); return; }
  const rect = trigger.value!.getBoundingClientRect();
  position.value = { top: `${rect.bottom + 8}px`, right: `${Math.max(12, window.innerWidth - rect.right)}px` };
  open.value = true; await nextTick(); menu.value?.querySelector<HTMLButtonElement>('button')?.focus();
}
function choose(action: 'profile' | 'settings' | 'logout') { close(true); if (action === 'profile') emit('profile'); else if (action === 'settings') emit('settings'); else emit('logout'); }
function keydown(event: KeyboardEvent) {
  if (event.key === 'Escape') { event.preventDefault(); close(true); return; }
  const buttons = Array.from(menu.value?.querySelectorAll<HTMLButtonElement>('button') || []);
  const index = buttons.indexOf(document.activeElement as HTMLButtonElement);
  if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
    event.preventDefault(); const next = event.key === 'Home' ? 0 : event.key === 'End' ? buttons.length - 1 : (index + (event.key === 'ArrowDown' ? 1 : -1) + buttons.length) % buttons.length;
    buttons[next]?.focus();
  }
}
function dismiss(event: Event) { const target = event.target as Node; if (!menu.value?.contains(target) && !trigger.value?.contains(target)) close(); }
function resize() { close(); }
onMounted(() => { document.addEventListener('pointerdown', dismiss); document.addEventListener('focusin', dismiss); window.addEventListener('resize', resize); });
onUnmounted(() => { document.removeEventListener('pointerdown', dismiss); document.removeEventListener('focusin', dismiss); window.removeEventListener('resize', resize); });
</script>
<template>
  <div class="header-account"><span v-if="!name" class="guest-label">Community guest</span><span v-else class="visually-hidden">{{ name }} · {{ admin ? 'Municipal administrator' : 'Citizen account' }}</span><button ref="trigger" type="button" class="avatar top-avatar" aria-label="Account" aria-haspopup="menu" :aria-expanded="open" @click="toggle" @keydown.down.prevent="toggle">{{ initial }}</button><button v-if="!name" type="button" class="text-button header-sign-in" @click="$emit('signIn')">Sign in</button></div>
  <Teleport to="body"><div v-if="open" ref="menu" class="account-dropdown" role="menu" aria-label="Account options" :style="position" @keydown="keydown"><div class="account-identity" role="presentation"><strong>{{ identity }}</strong><span>{{ admin ? 'Municipal administrator' : name ? 'Citizen account' : 'Community access' }}</span></div><button v-if="name" type="button" role="menuitem" @click="choose('profile')"><UserRound :size="20" aria-hidden="true" />My profile</button><button type="button" role="menuitem" @click="choose('settings')"><Settings :size="20" aria-hidden="true" />Settings</button><button v-if="name" type="button" role="menuitem" class="account-logout" @click="choose('logout')"><LogOut :size="20" aria-hidden="true" />Log out</button><button v-else type="button" role="menuitem" @click="close(); $emit('signIn')">Sign in</button></div></Teleport>
</template>
