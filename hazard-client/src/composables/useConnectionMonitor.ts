import { onMounted, onBeforeUnmount, watch, type Ref } from 'vue';
import { cancelConnectionCheck, checkConnection } from '../services/connection';

export function useConnectionMonitor(demo: Ref<boolean>) {
  let timer: ReturnType<typeof setInterval>;
  const check = () => { if (!demo.value) void checkConnection(); };
  const visible = () => { if (!document.hidden) check(); };
  watch(demo, value => { if (value) cancelConnectionCheck(); else check(); });
  onMounted(() => {
    window.addEventListener('online', check);
    window.addEventListener('offline', check);
    document.addEventListener('visibilitychange', visible);
    timer = setInterval(visible, 15000);
    check();
  });
  onBeforeUnmount(() => {
    clearInterval(timer);
    cancelConnectionCheck();
    window.removeEventListener('online', check);
    window.removeEventListener('offline', check);
    document.removeEventListener('visibilitychange', visible);
  });
}
