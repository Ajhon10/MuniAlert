import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const server = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const workspace = createHash('sha256').update(dirname(server).replaceAll('\\', '/').toLowerCase()).digest('hex').slice(0, 16);
const php = process.platform === 'win32' ? 'C:/php85/php.exe' : (process.env.BANTAY_PHP_BINARY || 'php');

export async function ensureApi() {
  const port = Number(process.env.HAZARD_API_PORT || 8000);
  const url = `http://127.0.0.1:${port}`;
  let existing;
  try { existing = await fetch(`${url}/api/health`, { signal: AbortSignal.timeout(4000) }); } catch {}
  if (existing) {
    if (existing.headers.get('x-bantay-dev') !== workspace) throw Error(`Port ${port} is occupied by a different server. Stop that server or choose HAZARD_API_PORT explicitly.`);
    if (!existing.ok || (await existing.json()).status !== 'ok') throw Error(`BANTAY API is running but its database health check failed. Check hazard-server/.env and storage/logs/laravel.log.`);
    console.log(`BANTAY API is already running: ${url}`);
    return null;
  }
  if (process.platform === 'win32' && !existsSync(php)) throw Error('Install/configure the required workstation PHP at C:/php85/php.exe.');
  const router = resolve(server, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');
  if (!existsSync(router)) throw Error('Backend dependencies are missing. Install the locked Composer dependencies using PHP 8.5.');
  // A direct child has a fixed port and can be stopped cleanly by the launcher.
  const child = spawn(php, ['-S', `127.0.0.1:${port}`, router], { cwd: resolve(server, 'public'), stdio: 'inherit', windowsHide: true });
  let failure;
  child.on('error', error => { failure = error; });
  try {
    const deadline = Date.now() + 30000;
    while (Date.now() < deadline) {
      if (failure) throw failure;
      if (child.exitCode !== null) throw Error(`BANTAY API exited (${child.exitCode}). Read the server error above.`);
      try {
        const response = await fetch(`${url}/api/health`, { signal: AbortSignal.timeout(1500) });
        if (response.headers.get('x-bantay-dev') === workspace && response.ok && (await response.json()).status === 'ok') {
          console.log(`BANTAY API ready: ${url} (${php})`);
          return child;
        }
      } catch {}
      await new Promise(resolve => setTimeout(resolve, 250));
    }
    throw Error('The API did not become healthy. Check hazard-server/.env database settings and storage/logs/laravel.log.');
  } catch (error) { child.kill(); throw error; }
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  try {
    const child = await ensureApi();
    if (child) {
      for (const signal of ['SIGINT', 'SIGTERM']) process.once(signal, () => child.kill());
      child.once('exit', code => { process.exitCode = code ?? 0; });
    }
  } catch (error) { console.error(error.message); process.exitCode = 1; }
}
