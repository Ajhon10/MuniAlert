import { ensureApi } from '../hazard-server/scripts/serve.mjs';
import { ensureWeb } from '../hazard-client/scripts/dev.mjs';

const owned = [];
let stopping = false;
function stop(code = 0) {
  if (stopping) return;
  stopping = true;
  for (const child of owned) child.kill();
  process.exitCode = code;
}
for (const signal of ['SIGINT', 'SIGTERM']) process.once(signal, () => stop());
try {
  const api = await ensureApi();
  if (api) owned.push(api);
  const web = await ensureWeb();
  if (web) owned.push(web);
  for (const child of owned) child.once('exit', code => { if (!stopping) stop(code ?? 1); });
  const port = Number(process.env.HAZARD_WEB_PORT || 5173);
  const response = await fetch(`http://127.0.0.1:${port}/api/health`, { signal: AbortSignal.timeout(5000) });
  if (!response.ok || (await response.json()).status !== 'ok') throw Error('The web proxy cannot reach the API. Check HAZARD_API_PORT and the API console.');
  console.log(`BANTAY is connected. Open http://localhost:${port}/`);
  if (!owned.length) console.log('Both services were already running; no duplicate processes were started.');
} catch (error) { console.error(error.message); stop(1); }
