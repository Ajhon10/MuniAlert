<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { BookOpen, Camera, MapPin, ShieldCheck, ClipboardCheck, ChartColumn, Newspaper, Route, Search, X, Play, ChevronDown, Printer, ArrowUpRight, TriangleAlert } from '@lucide/vue';
import identity from '../config/identity.json';
import { guideFeatures, manualSections, type ManualAudience } from '../data/help';
import type { User } from '../types';
import { native } from '../services/api';
const props = defineProps<{ user: User | null }>();
const emit = defineEmits<{ tour: []; report: []; review: [] }>();
const audience = ref<ManualAudience>(props.user?.role === 'admin' ? 'admin' : 'citizen');
const search = ref(''); const opened = ref('getting-started');
const icons = { camera: Camera, location: MapPin, shield: ShieldCheck, timeline: Route, review: ClipboardCheck, chart: ChartColumn, news: Newspaper };
const features = computed(() => guideFeatures.filter(item => item.audience === 'all' || item.audience === audience.value));
const sections = computed(() => manualSections.filter(item => (item.audience === 'all' || item.audience === audience.value) && (!search.value.trim() || `${item.title} ${item.summary} ${item.steps.join(' ')} ${item.note || ''} ${item.keywords || ''}`.toLowerCase().includes(search.value.trim().toLowerCase()))));
async function read(id: string) {
  search.value = ''; opened.value = id; await nextTick();
  const section = document.getElementById(`manual-${id}`); section?.scrollIntoView({ block: 'start', behavior: 'instant' }); section?.querySelector('summary')?.focus({ preventScroll: true });
}
async function printManual() {
  const details = Array.from(document.querySelectorAll<HTMLDetailsElement>('.guide-manual details'));
  const prior = details.map(item => item.open); details.forEach(item => item.open = true); await nextTick();
  const restore = () => details.forEach((item, index) => item.open = prior[index]!);
  window.addEventListener('afterprint', restore, { once: true });
  window.print();
}
</script>
<template><div class="user-guide">
  <section class="guide-hero"><div class="guide-kicker"><BookOpen :size="19" aria-hidden="true" /> Your guide to {{ identity.name }}</div><h2>Know what to do.<br>Report with confidence.</h2><p>Learn the features, try a highlighted walkthrough, or find step-by-step instructions.</p><p class="guide-filipino" lang="fil">Gabay sa pag-uulat, lokasyon at pagsubaybay ng insidente.</p><div class="guide-actions"><button class="button primary" @click="emit('tour')"><Play :size="18" aria-hidden="true" />Start feature tour</button><button v-if="user?.role !== 'admin'" class="button secondary" @click="emit('report')">Report a hazard<ArrowUpRight :size="18" aria-hidden="true" /></button><button v-else class="button secondary" @click="emit('review')">Review reports<ArrowUpRight :size="18" aria-hidden="true" /></button></div><details class="guide-project-title"><summary>About this capstone</summary><p>{{ identity.capstoneTitle }}</p><p><strong>{{ identity.name }}</strong> is the shorter application name for the municipal reporting system.</p></details></section>
  <div class="notice emergency guide-emergency"><TriangleAlert :size="23" aria-hidden="true" /><p><strong>Immediate danger? Call emergency services first.</strong>An app report is a record for municipal review. It does not guarantee emergency dispatch. Use only approved local hotlines.</p></div>
  <section class="guide-audience"><h2>Feature guide & user manual</h2><div class="guide-audience-buttons" role="group" aria-label="Choose manual audience"><button :aria-pressed="audience === 'citizen'" @click="audience = 'citizen'">Citizen guide</button><button :aria-pressed="audience === 'admin'" @click="audience = 'admin'">Administrator guide</button></div><p v-if="audience === 'admin' && user?.role !== 'admin'" class="field-help">Anyone can read these instructions. Only authorized administrators can use the administrative features.</p></section>
  <section class="guide-feature-grid" aria-label="Feature highlights"><article v-for="feature in features" :key="feature.id" class="guide-feature"><span class="guide-feature-icon"><component :is="icons[feature.icon]" :size="25" aria-hidden="true" /></span><h3>{{ feature.title }}</h3><p>{{ feature.description }}</p><button class="text-button" :aria-label="`Read instructions: ${feature.title}`" @click="read(feature.id)">Read instructions<ArrowUpRight :size="17" aria-hidden="true" /></button></article></section>
  <section class="guide-manual" aria-labelledby="manual-heading"><div class="guide-manual-heading"><div><h2 id="manual-heading">Step-by-step user manual</h2><p>Open a topic or search for what you need.</p></div><button v-if="!native" class="button secondary" @click="printManual"><Printer :size="18" aria-hidden="true" />Print / save PDF</button></div><div class="search-field guide-search"><Search :size="20" aria-hidden="true" /><input v-model="search" type="search" aria-label="Search user manual" placeholder="Search GPS, photos, updates…"><button v-if="search" class="icon-button" aria-label="Clear manual search" @click="search = ''"><X :size="18" /></button></div><p class="guide-result-count" role="status">{{ sections.length }} {{ sections.length === 1 ? 'topic' : 'topics' }}{{ search.trim() ? ' matching your search' : ` for ${audience === 'citizen' ? 'citizens' : 'administrators'}` }}</p>
  <details v-for="section in sections" :id="`manual-${section.id}`" :key="section.id" :open="!!search.trim() || opened === section.id" class="manual-topic"><summary><span><strong>{{ section.title }}</strong><span>{{ section.summary }}</span></span><ChevronDown :size="22" aria-hidden="true" /></summary><div class="manual-topic-body"><ol><li v-for="step in section.steps" :key="step">{{ step }}</li></ol><p v-if="section.note" class="manual-note">{{ section.note }}</p></div></details><div v-if="!sections.length" class="empty-state"><BookOpen :size="30" aria-hidden="true" /><h3>No matching instructions</h3><p>Try a shorter term, change the audience, or clear your search.</p><button class="button secondary" @click="search = ''">Clear search</button></div></section>
  <footer class="guide-identity"><ShieldCheck :size="26" aria-hidden="true" /><div><strong>{{ identity.name }} · {{ identity.subtitle }}</strong><p>{{ identity.capstoneTitle }}</p></div></footer>
</div></template>
