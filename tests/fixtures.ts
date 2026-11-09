import { test as base, expect } from '@playwright/test';
import { spawnSync } from 'node:child_process';
import { resolve } from 'node:path';
import { php, phpEnv } from '../scripts/php-runtime.mjs';

// Cases represent independent users/scenarios but share one localhost IP. Reset
// ONLY the isolated test cache between cases so their rate buckets do not add up
// into an artificial burst. Limits remain enforced throughout every test case.
const root = resolve(__dirname, '..');
export const test = base.extend<{ isolatedCache: void }>({
  isolatedCache: [async ({}, use) => {
    const expected = process.env.HAZARD_E2E_MYSQL === '1' ? 'municipal_hazards_e2e' : resolve(root, '.cache/e2e.sqlite');
    if (process.env.APP_ENV !== 'testing' || process.env.DB_DATABASE !== expected) throw Error('Refusing to clear cache outside the isolated E2E database.');
    const result = spawnSync(php, ['artisan', 'cache:clear', 'database', '--no-ansi'], {
      cwd: resolve(root, 'hazard-server'), env: phpEnv(), encoding: 'utf8', windowsHide: true,
    });
    if (result.status !== 0) throw Error(`Isolated test cache reset failed: ${result.stderr || result.stdout}`);
    await use();
  }, { auto: true }],
});
export { expect };
