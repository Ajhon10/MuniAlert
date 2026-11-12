<script setup lang="ts">
import { computed } from 'vue';
import { Award, Check, LockKeyhole, Sparkles } from '@lucide/vue';
import type { HazardAchievementTrack } from '../types';
import HazardIcon from './HazardIcon.vue';
const props = defineProps<{ tracks: HazardAchievementTrack[] }>();
const earned = computed(() => props.tracks.reduce((total, track) => total + track.levels.filter(level => level.earned).length, 0));
</script>
<template>
  <section v-if="tracks.length" class="hazard-achievements cms-card">
    <div class="achievement-collection-heading"><div><span class="eyebrow">Your community contribution</span><h2><Sparkles :size="22" aria-hidden="true" /><span>Hazard achievement collection</span></h2></div><span class="collection-count"><Award :size="19" aria-hidden="true" />{{ earned }} / {{ tracks.reduce((total, track) => total + track.levels.length, 0) }} earned</span></div>
    <p class="field-help">Build a collection in every hazard category through your own fact-checked, accepted reports. Each accepted report counts once; confirmations and pending reports do not add progress. Stay at a safe distance when reporting.</p>
    <div class="hazard-achievement-grid">
      <article v-for="track in tracks" :key="track.category.id" class="hazard-achievement-track" :class="track.category.id">
        <header><span class="hazard-medallion"><HazardIcon :category="track.category.id" :size="28" /></span><div><h3>{{ track.category.name }}</h3><p>{{ track.verified_count }} accepted {{ track.verified_count === 1 ? 'report' : 'reports' }}</p></div></header>
        <label class="achievement-progress"><span>{{ track.next_threshold ? `Next milestone: ${track.verified_count} / ${track.next_threshold}` : 'Collection complete' }}</span><progress :value="track.next_threshold ? Math.min(track.verified_count, track.next_threshold) : 15" :max="track.next_threshold || 15" :aria-label="`${track.category.name} achievement progress`"></progress></label>
        <p v-if="track.next_threshold" class="next-achievement">Next: {{ track.levels.find(level => !level.earned)?.name }}</p>
        <details :open="track.verified_count > 0"><summary>Explore three milestones</summary><ol class="hazard-levels"><li v-for="(level, index) in track.levels" :key="level.id" :class="{ earned: level.earned }"><span class="level-medallion" :class="`tier-${index + 1}`"><HazardIcon :category="track.category.id" :size="22" /><span>{{ index + 1 }}</span></span><div><strong>{{ level.name }}</strong><small>{{ level.threshold }} accepted {{ level.threshold === 1 ? 'report' : 'reports' }}</small><span class="level-state"><Check v-if="level.earned" :size="14" aria-hidden="true" /><LockKeyhole v-else :size="14" aria-hidden="true" />{{ level.earned ? 'Earned' : 'Not yet earned' }}</span></div></li></ol></details>
      </article>
    </div>
  </section>
</template>
