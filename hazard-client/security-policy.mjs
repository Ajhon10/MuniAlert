// Build-time public origins only. Never copy arbitrary environment values into a policy.
export function securityPolicy(env = {}) {
  const origins = new Set(['https://tiles.openfreemap.org', 'https://tile.openstreetmap.org',
    'https://firebaseinstallations.googleapis.com', 'https://fcmregistrations.googleapis.com', 'https://fcm.googleapis.com']);
  const accept = value => {
    if (!value) return;
    const url = new URL(value);
    if (url.protocol !== 'https:' || url.username || url.password || url.hostname.includes('*')) throw Error('Production CSP sources require exact credential-free HTTPS URLs.');
    origins.add(url.origin);
  };
  for (const key of ['VITE_API_BASE_URL', 'VITE_MAP_STYLE_URL', 'VITE_BARANGAY_GEOJSON_URL', 'VITE_CITY_GEOJSON_URL', 'VITE_PROVINCE_GEOJSON_URL']) accept(env[key]);
  for (const value of (env.VITE_CSP_EXTRA_ORIGINS || '').split(',').map(value => value.trim()).filter(Boolean)) {
    const url = new URL(value); if (url.origin !== value) throw Error('Extra CSP sources must be exact HTTPS origins.'); accept(value);
  }
  const sources = [...origins].sort().join(' ');
  const meta = `default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: ${sources}; font-src 'self' data:; connect-src 'self' ${sources}; worker-src 'self' blob:; manifest-src 'self'; base-uri 'none'; object-src 'none'; form-action 'self'`;
  return { meta, header: `${meta}; frame-ancestors 'none'` };
}
