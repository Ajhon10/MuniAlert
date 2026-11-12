import { createHash } from 'node:crypto';
import { spawnSync, execFileSync } from 'node:child_process';
import { mkdirSync, mkdtempSync, readFileSync, writeFileSync, existsSync, copyFileSync, lstatSync, rmSync } from 'node:fs';
import { resolve, dirname } from 'node:path';

const root = resolve(import.meta.dirname, '..'); const cache = resolve(root, '.cache');
const version = '8.30.1';
const windows = process.platform === 'win32';
if (!windows && (process.platform !== 'linux' || process.arch !== 'x64')) throw Error('Configure a reviewed Gitleaks build for this platform.');
const name = `gitleaks_${version}_${windows ? 'windows_x64.zip' : 'linux_x64.tar.gz'}`;
const expected = windows ? 'd29144deff3a68aa93ced33dddf84b7fdc26070add4aa0f4513094c8332afc4e' : '551f6fc83ea457d62a0d98237cbad105af8d557003051f41f3e7ca7b3f2470eb';
const folder = resolve(cache, `gitleaks-${version}`); mkdirSync(folder, { recursive: true });
const archive = resolve(folder, name); const executable = resolve(folder, windows ? 'gitleaks.exe' : 'gitleaks');
if (!existsSync(archive)) {
  const response = await fetch(`https://github.com/gitleaks/gitleaks/releases/download/v${version}/${name}`);
  if (!response.ok) throw Error('The pinned secret scanner could not be downloaded.');
  writeFileSync(archive, new Uint8Array(await response.arrayBuffer()));
}
if (createHash('sha256').update(readFileSync(archive)).digest('hex') !== expected) throw Error('Secret scanner archive checksum differs from the reviewed release.');
// Verify all archive paths before extracting into the task-owned tool cache.
const entries = execFileSync('tar', ['-tf', archive], { encoding: 'utf8', windowsHide: true }).trim().split(/\r?\n/);
if (entries.some(entry => entry.includes('..') || /^[\\/]|^[A-Za-z]:/.test(entry))) throw Error('Unsafe scanner archive path.');
execFileSync('tar', ['-xf', archive, '-C', folder], { windowsHide: true });

// Scan intended source, including untracked work, without reading private ignored
// .env files, uploads, databases, traces or dependency caches.
const files = execFileSync('git', ['ls-files', '--cached', '--others', '--exclude-standard', '-z'], { cwd: root, encoding: 'utf8', windowsHide: true }).split('\0').filter(Boolean);
const exported = mkdtempSync(resolve(cache, 'gitleaks-source-'));
const binary = /\.(png|jpe?g|gif|webp|pdf|mp4|wav|jar|woff2?|ttf|ico|zip|gz)$/i;
let copied = 0;
try {
  for (const file of files) {
    const source = resolve(root, file); const target = resolve(exported, file);
    if (!source.startsWith(root + (windows ? '\\' : '/')) || !target.startsWith(exported + (windows ? '\\' : '/'))) throw Error('Unsafe source path.');
    if (!existsSync(source) || binary.test(file)) continue;
    if (/[/\\]storage[/\\].*\.gitignore$/.test(file)) continue;
    if (lstatSync(source).isSymbolicLink()) throw Error('Review source symlinks before scanning.');
    if (/[/\\](?:vendor|node_modules|storage|\.cache)[/\\]|(^|[/\\])\.env(?:$|\.(?!example$))/.test(file)) throw Error('Private/runtime path selected for source scanning.');
    mkdirSync(dirname(target), { recursive: true }); copyFileSync(source, target); copied++;
  }
  const report = resolve(cache, 'gitleaks-report.json');
  const result = spawnSync(executable, ['dir', '--no-banner', '--redact=100', '--config', resolve(root, '.gitleaks.toml'), '--report-format', 'json', '--report-path', report, exported], { cwd: root, encoding: 'utf8', windowsHide: true });
  const findings = existsSync(report) ? JSON.parse(readFileSync(report, 'utf8')) : [];
  writeFileSync(resolve(cache, 'secret-scan-summary.json'), JSON.stringify({ version, sourceFiles: copied, passed: result.status === 0, findings: findings.map(item => ({ rule: item.RuleID, file: item.File.replaceAll('\\', '/').replace(exported.replaceAll('\\', '/') + '/', ''), line: item.StartLine })) }, null, 2));
  if (result.status !== 0) { console.error(`Secret scan failed: ${findings.length} finding(s). See the redacted .cache/secret-scan-summary.json.`); process.exitCode = 1; }
  else console.log(`Gitleaks ${version}: ${copied} source files checked; no findings. Ignored runtime data and Git history were not scanned.`);
} finally {
  if (!exported.startsWith(resolve(cache, 'gitleaks-source-'))) throw Error('Refusing unsafe temporary-source cleanup.');
  rmSync(exported, { recursive: true, force: true, maxRetries: 5, retryDelay: 200 });
}
