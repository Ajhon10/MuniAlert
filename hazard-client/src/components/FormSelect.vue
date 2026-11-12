<script setup lang="ts">
import { computed, getCurrentInstance, useAttrs } from 'vue';
defineOptions({ inheritAttrs: false });
const attrs = useAttrs(); const model = defineModel<unknown>(); const instance = getCurrentInstance();
const hasModel = computed(() => Object.hasOwn(instance?.vnode.props || {}, 'modelValue'));
</script>
<template><select v-if="hasModel" v-bind="attrs" v-model="model" class="form-control-select"><slot /></select><select v-else v-bind="attrs" class="form-control-select"><slot /></select></template>
