<script setup lang="ts">
import Modal from './Modal.vue';
defineProps<{ title: string; message: string; confirmLabel?: string; busy?: boolean; danger?: boolean; error?: string }>();
defineEmits<{ confirm: []; close: [] }>();
</script>
<template><Modal :title="title" @close="$emit('close')"><div class="form-body"><p>{{ message }}</p></div><template #footer><div class="confirm-actions"><button type="button" class="button" :class="danger ? 'danger' : 'primary'" :disabled="busy" @click="$emit('confirm')">{{ busy ? 'Please wait…' : confirmLabel || 'Confirm' }}</button></div></template></Modal></template>
