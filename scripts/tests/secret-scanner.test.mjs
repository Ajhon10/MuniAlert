import { test } from 'node:test';
import assert from 'node:assert/strict';
import { randomBytes } from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { mkdirSync, mkdtempSync, writeFileSync, readFileSync, rmSync } from 'node:fs';
import { resolve } from 'node:path';

test('pinned scanner detects synthetic credentials and redacts its report without broad evidence exemptions', () => {
  const root = resolve(import.meta.dirname, '../..'); const cache = resolve(root, '.cache');
  const folder = mkdtempSync(resolve(cache, 'gitleaks-negative-'));
  const secret = ['gh', 'p_'].join('') + randomBytes(18).toString('hex');
  const fixture = resolve(folder, 'documentation/evidence/social-auth-2026-11-04.json');
  mkdirSync(resolve(folder, 'documentation/evidence'), { recursive: true });
  writeFileSync(fixture, JSON.stringify({ 'api.ts': randomBytes(32).toString('hex'), api_key: secret }));
  const report = resolve(folder, 'report.json');
  const scanner = resolve(cache, 'gitleaks-8.30.1', process.platform === 'win32' ? 'gitleaks.exe' : 'gitleaks');
  try {
    const result = spawnSync(scanner, ['dir', '--no-banner', '--redact=100', '--config', resolve(root, '.gitleaks.toml'), '--report-format', 'json', '--report-path', report, folder], { encoding: 'utf8', windowsHide: true });
    assert.equal(result.status, 1, 'A credential inside a historical-evidence path must still fail.');
    const output = readFileSync(report, 'utf8');
    assert(!output.includes(secret), 'Reports must redact the complete synthetic credential.');
    const findings = JSON.parse(output);
    assert(findings.some(item => item.RuleID === 'github-pat'));
    assert(!findings.some(item => item.Match.includes('api.ts')), 'Only the reviewed file-hash shape is exempt.');
  } finally {
    if (!folder.startsWith(resolve(cache, 'gitleaks-negative-'))) throw Error('Unsafe fixture cleanup path.');
    rmSync(folder, { recursive: true, force: true, maxRetries: 5, retryDelay: 200 });
  }
});
