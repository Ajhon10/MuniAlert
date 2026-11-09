/// <reference lib="webworker" />
import { initializeApp } from 'firebase/app';
import { getMessaging } from 'firebase/messaging/sw';
const config = { apiKey: import.meta.env.VITE_FIREBASE_API_KEY, projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID, messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID, appId: import.meta.env.VITE_FIREBASE_APP_ID };
if (config.projectId) getMessaging(initializeApp(config));
