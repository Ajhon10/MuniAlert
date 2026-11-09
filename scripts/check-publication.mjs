import { spawnSync } from 'node:child_process';
import { readFileSync, statSync } from 'node:fs';
import { resolve } from 'node:path';
const root = resolve(import.meta.dirname, '..');
const git = args => {
  const result = spawnSync('git', args, { cwd: root, encoding: 'utf8', windowsHide: true });
  if (result.status !== 0) throw Error('Repository safety check requires the MuniAlert Git checkout and its correct owner.');
  return result.stdout;
};
if (process.argv.includes('--clean') && git(['status', '--porcelain']).trim()) throw Error('Commit the intended changes and rerun verification before pushing.');
const staged = git(['ls-files', '--stage', '-z']).split('\0').filter(Boolean);
const findings = [];
const binary = /\.(png|jpg|jpeg|webp|gif|ico|pdf|wav|mp4|jar|woff2?|ttf)$/i;
const deny = /(^|\/)(node_modules|vendor|\.cache|\.npm-cache|\.composer-cache|test-results|playwright-report)(\/|$)|(^|\/)\.env(?:$|\.(?!example$))|\.(?:sqlite|sqlite-journal|jks|keystore|p12|pfx|sql)$/i;
const secrets = [
  ['GitHub token', /\b(?:gh[pousr]_[A-Za-z0-9]{25,}|github_pat_[A-Za-z0-9_]{35,})\b/],
  ['Private key', /-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/],
  ['Cloud access key', /\b(?:AKIA|ASIA)[A-Z0-9]{16}\b/],
  ['Google API key', /\bAIza[0-9A-Za-z_-]{35}\b/],
  ['Credential in URL', /https?:\/\/[A-Za-z0-9_-]+:[A-Za-z0-9_%+-]{8,}@/],
];
for (const record of staged) {
  const tab = record.indexOf('\t'); const mode = record.slice(0, 6); const file = record.slice(tab + 1).replaceAll('\\', '/');
  if (mode === '160000') { findings.push(`${file}: embedded repository/submodule is not part of the selected single-repository layout`); continue; }
  if (deny.test(file) || /(?:google-services|firebase-credentials|service-account|auth)\.json$/i.test(file)) findings.push(`${file}: private/generated path`);
  if (/^hazard-server\/storage\/app\//.test(file) && !file.endsWith('/.gitignore')) findings.push(`${file}: private application storage`);
  const absolute = resolve(root, file); const size = statSync(absolute).size;
  if (size > 50 * 1024 * 1024) findings.push(`${file}: file exceeds the source-publication size limit`);
  if (binary.test(file)) continue;
  const content = readFileSync(absolute, 'utf8');
  for (const [label, pattern] of secrets) if (pattern.test(content)) findings.push(`${file}: possible ${label}`);
}
if (findings.length) throw Error(`Publication check failed (no secret values printed):\n${findings.join('\n')}`);
console.log(`Publication safety checked ${staged.length} tracked files; no forbidden paths or detected credential patterns. This is a targeted check, not a guarantee that every possible secret is detectable.`);
