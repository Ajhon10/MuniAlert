// Set an absolute HTTPS origin for Capacitor. Web deployments can use their own origin.
export const API_SERVER_URL = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/+$/, '');
