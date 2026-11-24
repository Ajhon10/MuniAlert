import { readonly, ref } from 'vue';
import { API_SERVER_URL } from '../config/api';

export type ConnectionStatus = 'checking' | 'connected' | 'offline' | 'server-unavailable';
const status = ref<ConnectionStatus>(navigator.onLine ? 'checking' : 'offline');
const checking = ref(false);
export const connectionStatus = readonly(status);
export const checkingConnection = readonly(checking);
let revision = 0;
let pending: AbortController | undefined;

export function connectionFailure(error?: unknown, httpStatus?: number): string {
  revision++;
  if (!navigator.onLine) {
    status.value = 'offline';
    return 'Not connected to the internet. Connect to Wi-Fi or mobile data and try again.';
  }
  status.value = 'server-unavailable';
  if (httpStatus) return `The municipal server is temporarily unavailable (HTTP ${httpStatus}). Please try again shortly.`;
  if (error instanceof Error && error.name === 'TimeoutError') return 'The municipal server took too long to respond. Please try again.';
  // Being online does not prove that the internet or the API is reachable.
  return 'Not connected to the municipal server. Check your internet connection or try again shortly; the server may be unavailable.';
}

export function cancelConnectionCheck() {
  revision++;
  pending?.abort();
  pending = undefined;
  checking.value = false;
}

export async function checkConnection() {
  if (!navigator.onLine) { cancelConnectionCheck(); connectionFailure(); return; }
  if (pending) return;
  const controller = new AbortController();
  pending = controller;
  checking.value = true;
  const version = revision;
  const timeout = setTimeout(() => controller.abort(), 8000);
  try {
    const response = await fetch(`${API_SERVER_URL}/api/health`, { headers: { Accept: 'application/json' }, credentials: 'omit', cache: 'no-store', signal: controller.signal });
    const data = await response.json();
    if (version !== revision) return;
    if (response.ok && data?.status === 'ok' && navigator.onLine) status.value = 'connected';
    else connectionFailure(undefined, response.status);
  } catch (error) {
    if (version === revision) connectionFailure(error);
  } finally {
    clearTimeout(timeout);
    if (pending === controller) { pending = undefined; checking.value = false; }
  }
}
