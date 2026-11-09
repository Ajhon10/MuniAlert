import { Capacitor } from '@capacitor/core';
import { Geolocation } from '@capacitor/geolocation';
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';
import type { GPS } from '../types';
export async function currentLocation(): Promise<GPS> {
  if (Capacitor.isNativePlatform()) { await Geolocation.requestPermissions(); const { coords } = await Geolocation.getCurrentPosition({ enableHighAccuracy: true, timeout: 15000 }); return { latitude: coords.latitude, longitude: coords.longitude, accuracy: coords.accuracy }; }
  return new Promise((resolve, reject) => { if (!navigator.geolocation) return reject(new Error('GPS is unavailable. Choose a location on the map.')); navigator.geolocation.getCurrentPosition(({ coords }) => resolve({ latitude: coords.latitude, longitude: coords.longitude, accuracy: coords.accuracy }), () => reject(new Error('Location access failed. Enable location access over HTTPS, or choose a pin manually.')), { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }); });
}
export async function capturePhoto(): Promise<File> {
  const result = await Camera.getPhoto({ source: CameraSource.Camera, resultType: CameraResultType.Uri, quality: 85, width: 1920, height: 1920, correctOrientation: true });
  if (!result.webPath) throw new Error('No photo was captured.');
  return compressPhoto(new File([await (await fetch(result.webPath)).blob()], 'camera.jpg', { type: 'image/jpeg' }));
}
export async function compressPhoto(file: File): Promise<File> {
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) throw new Error('Choose a JPEG, PNG, or WebP photo.');
  if (file.size > 20 * 1024 * 1024) throw new Error('Choose a photo under 20 MB.');
  const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
  try {
    if (bitmap.width * bitmap.height > 50000000) throw new Error('Choose a photo under 50 megapixels.');
    const scale = Math.min(1, 1920 / Math.max(bitmap.width, bitmap.height));
    const canvas = document.createElement('canvas'); canvas.width = Math.max(1, Math.round(bitmap.width * scale)); canvas.height = Math.max(1, Math.round(bitmap.height * scale));
    const context = canvas.getContext('2d'); if (!context) throw new Error('Image processing is unavailable.'); context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    for (const quality of [0.82, 0.68, 0.54, 0.4, 0.3]) { const blob = await new Promise<Blob | null>(resolve => canvas.toBlob(resolve, 'image/webp', quality)); if (blob && blob.size <= 1024 * 1024) return new File([blob], file.name.replace(/\.[^.]+$/, '') + (blob.type === 'image/webp' ? '.webp' : '.png'), { type: blob.type }); }
    throw new Error('This photo could not be reduced below 1 MB. Try a smaller image.');
  } finally { bitmap.close(); }
}
