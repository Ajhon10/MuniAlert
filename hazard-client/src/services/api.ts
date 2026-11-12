import { Capacitor } from '@capacitor/core';
import { requestStepUp, type MfaChallenge } from './accountSecurity';
import type { User } from '../types';
import { API_SERVER_URL as base } from '../config/api';
import { readSessionToken, saveSessionToken, clearSessionToken } from './sessionStore';
import { prepareServerValidation } from './feedback';
export const native = Capacitor.isNativePlatform();
let sessionGeneration = 0;
export async function clearToken() { sessionGeneration++; await clearSessionToken(); }
export async function csrf() { if (!native) await fetch(`${base}/sanctum/csrf-cookie`, { credentials: 'include', headers: { Accept: 'application/json' } }); }
async function request(path: string, options: RequestInit = {}): Promise<Response> {
  let response: Response;
  const generation = sessionGeneration;
  const token = await readSessionToken();
  const xsrf = document.cookie.split('; ').find(item => item.startsWith('XSRF-TOKEN='))?.slice(11);
  try { response = await fetch(`${base}${path}`, { ...options, credentials: native ? 'omit' : 'include', signal: options.signal || AbortSignal.timeout(30000), headers: { Accept: 'application/json', ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}), ...(token ? { Authorization: `Bearer ${token}` } : {}), ...(!native && xsrf ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrf) } : {}), ...options.headers } }); }
  catch { throw new Error('Unable to reach the municipal server. Check your connection and try again.'); }
  if (response.status === 401 && generation === sessionGeneration) {
    try { await clearToken(); }
    finally { window.dispatchEvent(new Event('session-expired')); }
  }
  return response;
}
export async function apiBlob(path: string): Promise<Blob> {
  if (!/^\/api\/reports\/[a-f0-9-]+\/photos\/[a-f0-9-]+$/.test(path)) throw new Error('Invalid private evidence link.');
  const response = await request(path);
  if (!response.ok) throw new Error('This evidence is unavailable or you do not have permission to view it.');
  if (!response.headers.get('Content-Type')?.startsWith('image/')) throw new Error('The evidence server returned an invalid image.');
  return response.blob();
}
export async function api<T>(path: string, options: RequestInit = {}, steppedUp = false): Promise<T> {
  const response = await request(path, options);
  if (response.status === 204 && response.ok) return undefined as T;
  let data;
  try { data = await response.json(); }
  catch { throw new Error('The municipal server returned an invalid response. Please retry or contact the administrator.'); }
  if (response.status === 428 && data?.reauthentication_required && !steppedUp) {
    await requestStepUp(data);
    return api<T>(path, options, true);
  }
  if (!response.ok) {
    const details = data?.errors ? Object.values(data.errors).flat().join(' ') : data?.message;
    const message = response.status === 419 ? 'Your session expired. Reload the page and sign in again.' : details || `Request failed (${response.status}).`;
    if (data?.errors) prepareServerValidation(message, data.errors);
    throw new Error(message);
  }
  return data as T;
}
export async function authenticate(register: boolean, values: { email: string; password: string; name?: string }) {
  await csrf();
  const result = await api<({ user: User; token?: string }) | MfaChallenge>(`${native ? '/api' : ''}/auth/${register ? 'register' : 'login'}`, { method: 'POST', body: JSON.stringify(values) });
  if ('mfa_required' in result) return result;
  return acceptSession(result);
}
export async function acceptSession(result: { user: User; token?: string }) {
  sessionGeneration++;
  if (native && result.token) {
    try { await saveSessionToken(result.token); }
    catch (error) {
      // Revoke a freshly issued credential if the OS vault cannot retain it.
      await revokeIssuedToken(result.token);
      throw error;
    }
  }
  return result.user;
}
export async function revokeIssuedToken(token: string) {
  await fetch(`${base}/api/auth/logout`, { method: 'POST', credentials: 'omit', signal: AbortSignal.timeout(10000), headers: { Accept: 'application/json', Authorization: `Bearer ${token}` } }).catch(() => {});
}
