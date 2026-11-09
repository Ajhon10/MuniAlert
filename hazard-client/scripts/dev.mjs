import { spawn } from 'node:child_process';
import { createHash } from 'node:crypto';
import { createRequire } from 'node:module';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const workspace = createHash('sha256').update(dirname(root).replaceAll('\\', '/').toLowerCase()).digest('hex').slice(0, 16);

export async function ensureWeb() {
  const port = Number(process.env.HAZARD_WEB_PORT || 5173);
  const apiPort = Number(process.env.HAZARD_API_PORT || 8000);
  const url = `http://127.0.0.1:${port}`;
  let existing;
  try { existing = await fetch(url, { headers: { Accept: 'text/html' }, signal: AbortSignal.timeout(4000) }); } catch {}
  if (existing) {
    if (existing.headers.get('x-bantay-dev') !== workspace || !existing.ok) throw Error(`Port ${port} is occupied by a different server. Stop that server or choose HAZARD_WEB_PORT explicitly.`);
    if (existing.headers.get('x-bantay-api-port') !== String(apiPort)) throw Error(`The existing BANTAY client proxies to another API port. Restart it with HAZARD_API_PORT=${apiPort}.`);
    console.log(`BANTAY web is already running: ${url}`);
    return null;
  }
  const require = createRequire(import.meta.url);
  const vite = resolve(dirname(require.resolve('vite/package.json')), 'bin/vite.js');
  const child = spawn(process.execPath, [vite, '--host', '0.0.0.0'], { cwd: root, stdio: 'inherit', env: process.env, windowsHide: true });
  let failure;
  child.on('error', error => { failure = error; });
  try {
    const deadline = Date.now() + 30000;
    while (Date.now() < deadline) {
      if (failure) throw failure;
      if (child.exitCode !== null) throw Error(`BANTAY web exited (${child.exitCode}). Read the startup error above.`);
      try {
        const response = await fetch(url, { headers: { Accept: 'text/html' }, signal: AbortSignal.timeout(1500) });
        if (response.ok && response.headers.get('x-bantay-dev') === workspace) {
          console.log(`BANTAY web ready: ${url}`);
          return child;
        }
      } catch {}
      await new Promise(resolve => setTimeout(resolve, 250));
    }
    throw Error('BANTAY web did not start. Read the Vite error above.');
  } catch (error) { child.kill(); throw error; }
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  try {
    const child = await ensureWeb();
    if (child) {
      for (const signal of ['SIGINT', 'SIGTERM']) process.once(signal, () => child.kill());
      child.once('exit', code => { process.exitCode = code ?? 0; });
    }
  } catch (error) { console.error(error.message); process.exitCode = 1; }
}
