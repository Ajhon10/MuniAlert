import { nextTick, onBeforeUnmount, onMounted, watch, type Ref } from 'vue';
import { reducedMotion } from '../services/accessibility';

export function usePageMotion(element: Ref<HTMLElement | null>, page: Ref<string>) {
  let animations: Animation[] = [];
  function stop() { animations.forEach(animation => animation.cancel()); animations = []; }
  async function enter() {
    stop(); await nextTick();
    if (!element.value || reducedMotion.value) return;
    animations.push(element.value.animate([{ opacity: 0.35 }, { opacity: 1 }], { duration: 360, easing: 'ease-out' }));
    const heading = element.value.querySelector('.page-heading');
    if (heading) animations.push(heading.animate([{ transform: 'translateY(12px)', opacity: 0 }, { transform: 'translateY(0)', opacity: 1 }], { duration: 420, easing: 'cubic-bezier(.16,1,.3,1)' }));
  }
  onMounted(enter); watch(page, enter, { flush: 'post' });
  watch(reducedMotion, value => { if (value) stop(); }); onBeforeUnmount(stop);
}
