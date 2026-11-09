import { spawnSync } from 'node:child_process';
import { cpSync, existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
// Build separately from Capacitor: Android retains its normal root asset paths.
const result = spawnSync(process.platform === 'win32' ? 'npm.cmd' : 'npm', ['run', 'build'], {
  cwd: root, env: { ...process.env, BANTAY_WEB_BASE: '/app/', VITE_API_BASE_URL: '' },
  shell: process.platform === 'win32', stdio: 'inherit', windowsHide: true,
});
if (result.status !== 0) process.exit(result.status ?? 1);
const source = resolve(root, 'hazard-client/dist-web');
const target = resolve(root, 'hazard-server/public/app');
const manifest = readFileSync(resolve(source, 'index.html'), 'utf8');
if (!manifest.includes('/app/assets/')) throw Error('Web build did not use the /app/ asset base.');
if (!existsSync(resolve(source, 'icon.svg'))) throw Error('Missing application icon.');
mkdirSync(target, { recursive: true });
cpSync(source, target, { recursive: true });
writeFileSync(resolve(target, '.bantay-web-build'), new Date().toISOString() + '\n');
console.log('Web assets packaged in hazard-server/public/app. Serve hazard-server/public as the ONLY document root.');
