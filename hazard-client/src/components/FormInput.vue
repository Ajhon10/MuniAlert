<script setup lang="ts">
import { computed, getCurrentInstance, ref, useAttrs } from 'vue';
defineOptions({ inheritAttrs: false });
const attrs = useAttrs();
const model = defineModel<unknown>();
const instance = getCurrentInstance();
const hasModel = computed(() => Object.hasOwn(instance?.vnode.props || {}, 'modelValue'));
const element = ref<HTMLInputElement>();
defineExpose({ element, focus: () => element.value?.focus(), click: () => element.value?.click() });
</script>
<template>
  <input v-if="hasModel && attrs.type !== 'file'" ref="element" v-bind="attrs" v-model="model" class="form-control-input">
  <input v-else ref="element" v-bind="attrs" class="form-control-input">
</template>
