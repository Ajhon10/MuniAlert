import { onBeforeUnmount, onMounted, ref } from 'vue';
import { lockModalScroll } from '../services/modalScroll';

/** Shared native top-layer/focus lifecycle for modal forms and the guided tour. */
export function useNativeDialog() {
  const dialog = ref<HTMLDialogElement>();
  let release: (() => void) | undefined;
  onMounted(() => { release = lockModalScroll(); dialog.value?.showModal(); });
  onBeforeUnmount(() => { dialog.value?.close(); release?.(); });
  return dialog;
}
