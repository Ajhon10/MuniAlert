import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { createHash } from 'node:crypto';
import { fileURLToPath } from 'node:url';
const workspace = createHash('sha256').update(fileURLToPath(new URL('../', import.meta.url)).replace(/[\\/]+$/, '').replaceAll('\\', '/').toLowerCase()).digest('hex').slice(0, 16);
const apiPort = Number(process.env.HAZARD_API_PORT || 8000);
const webPort = Number(process.env.HAZARD_WEB_PORT || 5173);
export default defineConfig({ base: process.env.BANTAY_WEB_BASE || '/', plugins: [vue(), { name: 'bantay-dev-identity', apply: 'serve', configureServer(server) { server.middlewares.use((_request, response, next) => { response.setHeader('X-Bantay-Dev', workspace); response.setHeader('X-Bantay-Api-Port', String(apiPort)); next(); }); } }], server: { port: webPort, strictPort: true, proxy: Object.fromEntries(['/api', '/auth', '/sanctum', '/storage'].map(path => [path, { target: `http://127.0.0.1:${apiPort}`, changeOrigin: false }])) }, build: { outDir: process.env.BANTAY_WEB_BASE ? 'dist-web' : 'dist', chunkSizeWarningLimit: 1200 } });
