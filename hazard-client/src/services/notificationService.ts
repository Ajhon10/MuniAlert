import { Capacitor } from '@capacitor/core';
import { PushNotifications } from '@capacitor/push-notifications';
import { initializeApp, getApps } from 'firebase/app';
import { getMessaging, getToken, isSupported, onMessage } from 'firebase/messaging';
import workerUrl from '../firebase-messaging-sw.ts?worker&url';
import { api } from './api';
export async function enablePush(onNotice: (text: string) => void) {
  let token: string;
  if (Capacitor.isNativePlatform()) {
    if (Capacitor.getPlatform() !== 'android') throw new Error('Native push is currently supported on Android. Report updates remain available in Notifications.');
    const permission = await PushNotifications.requestPermissions();
    if (permission.receive !== 'granted') throw new Error('Notification permission was not granted.');
    const listeners: Array<{ remove: () => Promise<void> }> = [];
    let timeout: ReturnType<typeof setTimeout> | undefined;
    try {
      token = await new Promise<string>((resolve, reject) => {
        timeout = setTimeout(() => reject(new Error('Push registration timed out. Check the Firebase Android configuration.')), 20000);
        void (async () => {
          listeners.push(await PushNotifications.addListener('registration', result => resolve(result.value)));
          listeners.push(await PushNotifications.addListener('registrationError', error => reject(new Error(error.error))));
          await PushNotifications.register();
        })().catch(reject);
      });
    } finally {
      clearTimeout(timeout);
      await Promise.all(listeners.map(listener => listener.remove()));
    }
  } else {
    if (!import.meta.env.VITE_FIREBASE_PROJECT_ID || !import.meta.env.VITE_FIREBASE_VAPID_KEY) throw new Error('Web push is not configured yet. Report updates are still available in Notifications.');
    if (!await isSupported()) throw new Error('Push notifications are not supported in this browser.');
    if (await Notification.requestPermission() !== 'granted') throw new Error('Notification permission was not granted.');
    const app = getApps()[0] || initializeApp({ apiKey: import.meta.env.VITE_FIREBASE_API_KEY, projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID, messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID, appId: import.meta.env.VITE_FIREBASE_APP_ID });
    const messaging = getMessaging(app); const registration = await navigator.serviceWorker.register(workerUrl);
    token = await getToken(messaging, { vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY, serviceWorkerRegistration: registration });
    onMessage(messaging, payload => onNotice(payload.notification?.body || 'A report has been updated.'));
  }
  await api('/api/devices', { method: 'POST', body: JSON.stringify({ token, platform: Capacitor.isNativePlatform() ? Capacitor.getPlatform() : 'web' }) });
  localStorage.setItem('bantay.push-token', token);
}
export async function unregisterDevice() { const token = localStorage.getItem('bantay.push-token'); if (token) { await api('/api/devices', { method: 'DELETE', body: JSON.stringify({ token }) }); localStorage.removeItem('bantay.push-token'); } }
