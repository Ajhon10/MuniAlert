<script setup lang="ts">
import { useAttrs } from 'vue';
defineOptions({ inheritAttrs: false });
const attrs = useAttrs(); const model = defineModel<string>();
</script>
<template><textarea v-bind="attrs" v-model="model" class="form-control-textarea" /></template>
