import { defineConfig, loadEnv } from 'vite';
// @ts-expect-error The policy builder is shared with Node verification scripts.
import { securityPolicy } from './security-policy.mjs';
import vue from '@vitejs/plugin-vue';
import { createHash } from 'node:crypto';
import { fileURLToPath } from 'node:url';
const workspace = createHash('sha256').update(fileURLToPath(new URL('../', import.meta.url)).replace(/[\\/]+$/, '').replaceAll('\\', '/').toLowerCase()).digest('hex').slice(0, 16);
const apiPort = Number(process.env.HAZARD_API_PORT || 8000);
const webPort = Number(process.env.HAZARD_WEB_PORT || 5173);
export default defineConfig(({ mode, command }) => {
  const policy = securityPolicy(command === 'build' ? { ...loadEnv(mode, process.cwd(), 'VITE_'), ...process.env } : {});
  return { base: process.env.BANTAY_WEB_BASE || '/', plugins: [vue(), {
    name: 'bantay-production-csp', apply: 'build',
    transformIndexHtml(html) { return html.replace(/(<meta charset="[^"]+">)/, `$1<meta http-equiv="Content-Security-Policy" content="${policy.meta.replaceAll('&', '&amp;').replaceAll('"', '&quot;')}">`); },
    generateBundle() {
      this.emitFile({ type: 'asset', fileName: 'security-policy.json', source: JSON.stringify({ policy: policy.header }) });
      this.emitFile({ type: 'asset', fileName: '.htaccess', source: `<IfModule mod_headers.c>\nHeader always set Content-Security-Policy "${policy.header}"\nHeader always set X-Content-Type-Options "nosniff"\nHeader always set X-Frame-Options "DENY"\nHeader always set Referrer-Policy "no-referrer"\n</IfModule>\n` });
    },
  }, { name: 'bantay-dev-identity', apply: 'serve', configureServer(server) { server.middlewares.use((_request, response, next) => { response.setHeader('X-Bantay-Dev', workspace); response.setHeader('X-Bantay-Api-Port', String(apiPort)); next(); }); } }], server: { port: webPort, strictPort: true, proxy: Object.fromEntries(['/api', '/auth', '/sanctum', '/storage', '/news'].map(path => [path, { target: `http://127.0.0.1:${apiPort}`, changeOrigin: false }])) }, build: { outDir: process.env.BANTAY_WEB_BASE ? 'dist-web' : 'dist', chunkSizeWarningLimit: 1200 } };
});
