import { Capacitor } from '@capacitor/core';
import { SecureStorage } from '@aparajita/capacitor-secure-storage';
import { legacyStorage } from './legacyStorage';

const key = 'munialert.session';
let token: string | null = null;
let restored: Promise<void> | undefined;

export async function readSessionToken(): Promise<string | null> {
  if (!Capacitor.isNativePlatform()) return null;
  restored ??= (async () => {
    try {
      let saved = await SecureStorage.get(key);
      if (typeof saved !== 'string') {
        const legacy = await SecureStorage.get(legacyStorage.session);
        if (typeof legacy === 'string') {
          await SecureStorage.set(key, legacy);
          await SecureStorage.remove(legacyStorage.session);
          saved = legacy;
        }
      }
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
  try { await SecureStorage.set(key, value); await SecureStorage.remove(legacyStorage.session); }
  catch { throw new Error('Unable to securely save your session on this device. Please try signing in again.'); }
  token = value;
  restored = Promise.resolve();
}

export async function clearSessionToken(): Promise<void> {
  token = null;
  restored = Promise.resolve();
  if (Capacitor.isNativePlatform()) await Promise.all([SecureStorage.remove(key), SecureStorage.remove(legacyStorage.session)]);
}
