import { ref } from 'vue';
import { pendingMfa, mfaIntent, type MfaChallenge } from './accountSecurity';
import { App } from '@capacitor/app';
import { Browser } from '@capacitor/browser';
import { api, acceptSession, csrf, native, revokeIssuedToken } from './api';
import { API_SERVER_URL } from '../config/api';
import { clearPendingSocial, readPendingSocial, savePendingSocial } from './sessionStore';
import type { User } from '../types';

export type SocialProvider = 'google' | 'facebook';
export type SocialResult = { user: User; intent: string; linked: boolean };
export const socialBusy = ref(false);
export const socialError = ref('');
let initialized: Promise<void> | undefined;
let finishing = false;
let attemptGeneration = 0;
let expiryTimer: ReturnType<typeof setTimeout> | undefined;
const messages: Record<string, string> = {
  cancelled: 'Sign-in was cancelled. You can try again or use email.',
  expired: 'This sign-in request expired. Please start again.',
  unavailable: 'This sign-in method is not available right now. Use email to continue.',
  existing_account: 'An account already uses this email. Sign in with your existing method, then connect this provider in My profile using your MuniAlert password.',
  account_in_use: 'That provider account is already connected to a MuniAlert account. Use that account to sign in.',
  email_required: 'An email address is needed to create your account. Allow email access with the provider or use email registration.',
  email_unverified: 'Verify your email with Google before creating your MuniAlert account, or register with email.',
  provider_failed: 'The provider could not complete sign-in. Please try again or use email.',
};
export function socialMessage(reason: string | null) { return messages[reason || ''] || messages.provider_failed!; }
const base64url = (bytes: Uint8Array) => btoa(String.fromCharCode(...bytes)).replaceAll('+', '-').replaceAll('/', '_').replaceAll('=', '');

export async function cancelSocialAuth() {
  attemptGeneration++;
  clearTimeout(expiryTimer);
  socialBusy.value = false;
  if (native) { await clearPendingSocial(); await Browser.close().catch(() => {}); }
}

async function receiveNative(url: string) {
  let target: URL;
  try { target = new URL(url); } catch { return; }
  if (target.protocol !== 'ph.municipal.bantay:' || target.host !== 'auth' || target.pathname !== '/callback' || finishing) return;
  const pending = await readPendingSocial();
  if (finishing || !pending || pending.attempt !== target.searchParams.get('attempt')) return;
  finishing = true;
  const generation = attemptGeneration;
  socialBusy.value = true;
  try {
    if (pending.expiresAt < Date.now()) throw new Error(socialMessage('expired'));
    if (!['success', 'linked'].includes(target.searchParams.get('social_auth') || '')) throw new Error(socialMessage(target.searchParams.get('social_error')));
    const result = await api<(SocialResult & { token?: string }) | (MfaChallenge & { intent: string; linked: false })>('/api/auth/social/exchange', { method: 'POST', body: JSON.stringify({ attempt: pending.attempt, code: target.searchParams.get('code'), code_verifier: pending.verifier }) });
    if ('mfa_required' in result) { if (generation === attemptGeneration) { mfaIntent.value = result.intent; pendingMfa.value = result; } return; }
    if (generation !== attemptGeneration) { if (result.token) await revokeIssuedToken(result.token); return; }
    if (!result.linked) {
      if (!result.token) throw new Error('Sign-in could not be securely completed. Please try again.');
      await acceptSession(result);
    }
    window.dispatchEvent(new CustomEvent<SocialResult>('social-authenticated', { detail: { user: result.user, intent: result.intent, linked: result.linked } }));
  } catch (error) {
    socialError.value = (error as Error).message;
  } finally {
    await cancelSocialAuth().catch(() => { socialBusy.value = false; });
    finishing = false;
  }
}

export async function initializeSocialAuth() {
  if (!native) return;
  initialized ??= (async () => {
    await App.addListener('appUrlOpen', ({ url }) => { void receiveNative(url).catch(() => { socialError.value = 'Unable to unlock your sign-in request. Please start again.'; socialBusy.value = false; }); });
    // Android custom tabs may remain open after a deep link. The app offers an
    // explicit cancel control; browserFinished is intentionally not used to
    // discard a pending cold-launch callback before it can be delivered.
    const launch = await App.getLaunchUrl();
    if (launch?.url) await receiveNative(launch.url);
    const pending = await readPendingSocial();
    if (pending && pending.expiresAt <= Date.now()) await cancelSocialAuth();
    else if (pending) {
      socialBusy.value = true;
      expiryTimer = setTimeout(() => { void cancelSocialAuth().catch(() => {}); }, pending.expiresAt - Date.now());
    }
  })().catch(() => { initialized = undefined; });
  await initialized;
}

export async function startSocialAuth(provider: SocialProvider, options: { intent?: string; mode?: 'login' | 'link'; currentPassword?: string } = {}) {
  if (socialBusy.value) return;
  socialBusy.value = true;
  socialError.value = '';
  try {
    let verifier: string | undefined;
    let challenge: string | undefined;
    if (native) {
      await initializeSocialAuth();
      if (!API_SERVER_URL.startsWith('https://')) throw new Error('Secure sign-in is unavailable in this app build. Use email or contact your municipality.');
      verifier = base64url(crypto.getRandomValues(new Uint8Array(32)));
      challenge = base64url(new Uint8Array(await crypto.subtle.digest('SHA-256', new TextEncoder().encode(verifier))));
    }
    await csrf();
    const result = await api<{ attempt: string; redirect_url: string }>(`/api/auth/social/${provider}/attempts`, { method: 'POST', body: JSON.stringify({ platform: native ? 'android' : 'web', mode: options.mode || 'login', intent: options.intent || 'overview', ...(native ? { code_challenge: challenge } : { return_origin: location.origin }), ...(options.currentPassword ? { current_password: options.currentPassword } : {}) }) });
    const target = new URL(result.redirect_url, location.origin);
    const server = new URL(API_SERVER_URL || location.origin);
    if (target.origin !== server.origin || target.pathname !== `/auth/social/${provider}/redirect` || target.searchParams.get('attempt') !== result.attempt) throw new Error('The sign-in address could not be verified. Please contact the administrator.');
    if (native && verifier) {
      await savePendingSocial({ attempt: result.attempt, verifier, expiresAt: Date.now() + 10 * 60000 });
      expiryTimer = setTimeout(() => { void cancelSocialAuth().catch(() => {}); }, 10 * 60000);
      await Browser.open({ url: target.href });
    } else location.assign(target.href);
  } catch (error) {
    socialError.value = (error as Error).message;
    await cancelSocialAuth().catch(() => { socialBusy.value = false; });
  }
}
