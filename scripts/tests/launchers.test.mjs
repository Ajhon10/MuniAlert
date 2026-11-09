import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { createHash } from 'node:crypto';
import { spawn } from 'node:child_process';
import { resolve } from 'node:path';
import { ensureApi } from '../../hazard-server/scripts/serve.mjs';
import { ensureWeb } from '../../hazard-client/scripts/dev.mjs';

const root = resolve(import.meta.dirname, '../..');
const workspace = createHash('sha256').update(root.replaceAll('\\', '/').toLowerCase()).digest('hex').slice(0, 16);

async function fixture(run, { identity = workspace, status = 200, apiPort } = {}) {
  const previous = { api: process.env.HAZARD_API_PORT, web: process.env.HAZARD_WEB_PORT };
  const server = createServer((request, response) => {
    response.writeHead(status, { 'Content-Type': 'application/json', 'X-Bantay-Dev': identity, 'X-Bantay-Api-Port': String(apiPort ?? server.address().port) });
    response.end(JSON.stringify({ status: 'ok' }));
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const port = server.address().port;
  process.env.HAZARD_API_PORT = process.env.HAZARD_WEB_PORT = String(port);
  try { await run(port); }
  finally {
    for (const [key, value] of [['HAZARD_API_PORT', previous.api], ['HAZARD_WEB_PORT', previous.web]]) {
      if (value === undefined) delete process.env[key]; else process.env[key] = value;
    }
    await new Promise(resolve => server.close(resolve));
  }
}

test('reuses only healthy web and API services belonging to this workspace', () => fixture(async () => {
  assert.equal(await ensureApi(), null);
  assert.equal(await ensureWeb(), null);
}));

test('refuses an unrelated port occupant and leaves it running', () => fixture(async port => {
  await assert.rejects(ensureApi(), /different server/);
  await assert.rejects(ensureWeb(), /different server/);
  assert.equal((await fetch(`http://127.0.0.1:${port}`)).status, 200);
}, { identity: 'another-project' }));

test('reports a database-health failure instead of attempting a duplicate API', () => fixture(async () => {
  await assert.rejects(ensureApi(), /database health check failed/);
}, { status: 503 }));

test('refuses a running client configured for another API port', () => fixture(async () => {
  await assert.rejects(ensureWeb(), /another API port/);
}, { apiPort: -1 }));

test('combined launcher verifies the proxy and exits cleanly when both services already run', () => fixture(async () => {
  const child = spawn(process.execPath, ['scripts/dev.mjs'], { cwd: root, env: process.env, windowsHide: true });
  let stdout = ''; let stderr = '';
  child.stdout.on('data', value => { stdout += value; });
  child.stderr.on('data', value => { stderr += value; });
  const timeout = setTimeout(() => child.kill(), 10000);
  try {
    const code = await new Promise((resolve, reject) => { child.once('error', reject); child.once('exit', resolve); });
    assert.equal(code, 0, stderr);
    assert.match(stdout, /BANTAY is connected/);
    assert.match(stdout, /no duplicate processes were started/);
  } finally { clearTimeout(timeout); }
}));
