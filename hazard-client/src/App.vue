<script setup lang="ts">
import DashboardView from "./views/DashboardView.vue";
import ToastHost from './components/ToastHost.vue';
import ReauthenticationDialog from './components/ReauthenticationDialog.vue';
import RecoveryCodesDialog from './components/RecoveryCodesDialog.vue';
import { stepUp, recoveryCodes } from './services/accountSecurity';
</script>
<template><DashboardView /><ReauthenticationDialog v-if="stepUp" /><RecoveryCodesDialog v-if="recoveryCodes.length" /><ToastHost /></template>
