<script setup lang="ts" generic="T extends { id: string | number }">
defineProps<{ rows: T[]; columns: { label: string; hidden?: boolean }[] }>();
</script>
<template><div class="table-scroll"><table class="data-table"><thead><tr><th v-for="(column, index) in columns" :key="index"><span :class="{ 'visually-hidden': column.hidden }">{{ column.label }}</span></th></tr></thead><tbody><tr v-for="row in rows" :key="row.id"><slot name="row" :row="row" /></tr></tbody></table></div></template>
<style>
.data-table :is(th, td) { font-size: .9375rem; line-height: 1.5; }
.data-table td small { font-size: .8125rem; line-height: 1.5; }
</style>
