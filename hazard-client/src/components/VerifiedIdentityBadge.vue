<script setup lang="ts">
import { BadgeCheck } from '@lucide/vue';
</script>
<template><span class="verified-identity-badge" role="img" aria-label="Identity verified" title="Government ID reviewed by an authorized municipal reviewer. This does not certify reports as true."><BadgeCheck :size="22" aria-hidden="true" /></span></template>
<style scoped>
.verified-identity-badge { display: inline-flex; flex: 0 0 auto; align-items: center; vertical-align: middle; }
.verified-identity-badge :deep(svg) { fill: #0866cf; stroke: white; stroke-width: 2; }
</style>
