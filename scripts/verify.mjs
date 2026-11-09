import { phpEnv } from './php-runtime.mjs';
import { spawnSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
const root = resolve(import.meta.dirname, '..');
const checks = [
  ['Repository publication safety', 'node scripts/check-publication.mjs'],
  ['Composer manifest', 'composer --working-dir=hazard-server validate --strict'],
  ['PHP formatting', 'php hazard-server/vendor/bin/pint --test hazard-server/app hazard-server/bootstrap/app.php hazard-server/bootstrap/providers.php hazard-server/config hazard-server/database hazard-server/routes hazard-server/tests'],
  ['Development launcher checks', 'node --test scripts/tests/launchers.test.mjs'],
  ['Vue type checking', 'npm run typecheck'],
  ['Frontend production build', 'npm run build'],
  ['Single-origin web package', 'npm run build:web'],
  ['Laravel feature tests', 'npm test'],
  ['Browser integration tests', 'npm run test:e2e'],
  ['Capacitor synchronization', 'npm exec -w hazard-client -- cap sync android'],
  ['Node dependency audit', 'npm audit --audit-level=moderate --cache .npm-cache'],
  ['PHP dependency audit', 'composer --working-dir=hazard-server audit --no-interaction'],
  ['Documentation consistency', 'node scripts/check-docs.mjs'],
  ['Promotional source and dependencies', 'node scripts/verify-promo.mjs'],
];
const report = { startedAt: new Date().toISOString(), passed: false, checks: [] };
mkdirSync(resolve(root, '.cache'), { recursive: true });
for (const [name, command] of checks) {
  console.log(`\nChecking: ${name}`);
  const result = spawnSync(command, { cwd: root, shell: true, stdio: 'inherit', env: phpEnv({ CI: '1', COMPOSER_CACHE_DIR: resolve(root, 'hazard-server/.composer-cache') }) });
  report.checks.push({ name, command, passed: result.status === 0, exitCode: result.status });
  writeFileSync(resolve(root, '.cache/verification-last.json'), JSON.stringify(report, null, 2));
  if (result.status !== 0) { console.error(`\nVerification failed: ${name}. GitHub push is blocked.`); process.exit(1); }
}
report.passed = true; report.completedAt = new Date().toISOString();
writeFileSync(resolve(root, '.cache/verification-last.json'), JSON.stringify(report, null, 2));
console.log('\nAll required checks passed. This does not itself authorize a GitHub push.');
