import { php, phpEnv } from './php-runtime.mjs';
import { spawn, spawnSync } from 'node:child_process';
import { mkdirSync, writeFileSync, openSync, closeSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import { randomBytes } from 'node:crypto';
const root = resolve(import.meta.dirname, '..');
const services = []; const logs = [];
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
async function responds(url) { try { await fetch(url, { signal: AbortSignal.timeout(800) }); return true; } catch { return false; } }
function stop() { for (const service of services) service.kill(); for (const log of logs) closeSync(log); }
for (const signal of ['SIGTERM', 'SIGINT']) process.once(signal, () => { stop(); process.exit(1); });
let result = 1;
try {
  const apiPort = process.env.HAZARD_API_PORT || '18000';
  const webPort = process.env.HAZARD_WEB_PORT || '15173';
  const apiUrl = `http://127.0.0.1:${apiPort}`;
  const webUrl = `http://127.0.0.1:${webPort}`;
  for (const url of [`${apiUrl}/api/health`, webUrl]) if (await responds(url)) throw Error(`Stop the server at ${url} before running isolated browser tests.`);
  mkdirSync(resolve(root, '.cache'), { recursive: true });
  const sqlite = resolve(root, '.cache/e2e.sqlite');
  const mysql = process.env.HAZARD_E2E_MYSQL === '1';
  if (!mysql) writeFileSync(sqlite, '');
  const env = { ...phpEnv(), HAZARD_API_PORT: apiPort, HAZARD_WEB_PORT: webPort, APP_ENV: 'testing', APP_DEBUG: 'false', BCRYPT_ROUNDS: '4', APP_URL: apiUrl, FRONTEND_URLS: `${webUrl},http://localhost:${webPort}`, APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', DB_CONNECTION: mysql ? 'mysql' : 'sqlite', DB_DATABASE: mysql ? 'municipal_hazards_e2e' : sqlite, SESSION_DRIVER: 'file', CACHE_STORE: 'database', QUEUE_CONNECTION: 'sync', GOOGLE_CLIENT_ID: '', GOOGLE_CLIENT_SECRET: '', GOOGLE_REDIRECT_URI: '', FACEBOOK_CLIENT_ID: '', FACEBOOK_CLIENT_SECRET: '', FACEBOOK_REDIRECT_URI: '', FCM_PROJECT_ID: '', GOOGLE_APPLICATION_CREDENTIALS: '', HAZARD_EVIDENCE_DISK: 'evidence', SANCTUM_STATEFUL_DOMAINS: `127.0.0.1:${webPort},localhost:${webPort}`, HAZARD_DEMO_ADMIN_PASSWORD: randomBytes(24).toString('base64url'), HAZARD_DEMO_CITIZEN_ONE_PASSWORD: randomBytes(24).toString('base64url'), HAZARD_DEMO_CITIZEN_TWO_PASSWORD: randomBytes(24).toString('base64url') };
  // Exercise both the Vite proxy and the packaged same-origin web deployment.
  env.SANCTUM_STATEFUL_DOMAINS += `,127.0.0.1:${apiPort},localhost:${apiPort}`;
  env.FRONTEND_URLS += `,${apiUrl}`;
  env.E2E_ADMIN_PASSWORD = env.HAZARD_DEMO_ADMIN_PASSWORD;
  env.E2E_CITIZEN_PASSWORD = env.HAZARD_DEMO_CITIZEN_ONE_PASSWORD;
  if (!env.PLAYWRIGHT_BROWSERS_PATH && existsSync(resolve(root, '.cache/playwright'))) env.PLAYWRIGHT_BROWSERS_PATH = resolve(root, '.cache/playwright');
  const migration = spawnSync(php, ['artisan', 'migrate:fresh', '--seed', '--force', '--no-ansi'], { cwd: resolve(root, 'hazard-server'), env, stdio: 'inherit' });
  if (migration.status !== 0) throw Error('Isolated test database setup failed.');
  const accounts = spawnSync(php, ['artisan', 'db:seed', '--class=DemoAccountSeeder', '--force', '--no-ansi'], { cwd: resolve(root, 'hazard-server'), env, stdio: 'inherit' });
  if (accounts.status !== 0) throw Error('Isolated browser test accounts could not be created.');
  if (!existsSync(resolve(root, 'hazard-server/public/storage'))) spawnSync(php, ['artisan', 'storage:link'], { cwd: resolve(root, 'hazard-server'), env, stdio: 'inherit' });
  for (const [name, executable, args, cwd] of [
    ['api', php, ['-S', `127.0.0.1:${apiPort}`, resolve(root, 'hazard-server/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], resolve(root, 'hazard-server/public')],
    ['web', process.execPath, [resolve(root, 'node_modules/vite/bin/vite.js'), '--host', '127.0.0.1', '--port', webPort], resolve(root, 'hazard-client')],
  ]) { const log = openSync(resolve(root, `.cache/e2e-${name}.log`), 'w'); logs.push(log); services.push(spawn(executable, args, { cwd, env, windowsHide: true, stdio: ['ignore', log, log] })); }
  for (const url of [`${apiUrl}/api/health`, webUrl]) {
    const deadline = Date.now() + 30000;
    while (!await responds(url)) { if (Date.now() > deadline) throw Error(`Test server failed to start: ${url}. See .cache/e2e-*.log.`); await delay(200); }
  }
  result = await new Promise(resolveResult => { const test = spawn(process.execPath, [resolve(root, 'node_modules/@playwright/test/cli.js'), 'test', ...process.argv.slice(2)], { cwd: root, env, stdio: 'inherit', windowsHide: true }); test.on('exit', code => resolveResult(code ?? 1)); test.on('error', () => resolveResult(1)); });
} catch (error) { console.error(error.message); } finally { stop(); }
process.exitCode = result;
