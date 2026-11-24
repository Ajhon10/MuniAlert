import { Capacitor } from '@capacitor/core';
import { SecureStorage } from '@aparajita/capacitor-secure-storage';

const key = 'bantay.session';
let token: string | null = null;
let restored: Promise<void> | undefined;

export async function readSessionToken(): Promise<string | null> {
  if (!Capacitor.isNativePlatform()) return null;
  restored ??= (async () => {
    try {
      const saved = await SecureStorage.get(key);
      token = typeof saved === 'string' ? saved : null;
    } catch {
      restored = undefined;
      throw new Error('Unable to unlock your saved session. Unlock the device and try again.');
    }
  })();
  await restored;
  return token;
}

export async function saveSessionToken(value: string): Promise<void> {
  if (!Capacitor.isNativePlatform()) return;
  try { await SecureStorage.set(key, value); }
  catch { throw new Error('Unable to securely save your session on this device. Please try signing in again.'); }
  token = value;
  restored = Promise.resolve();
}

export async function clearSessionToken(): Promise<void> {
  token = null;
  restored = Promise.resolve();
  if (Capacitor.isNativePlatform()) await SecureStorage.remove(key);
}
