import { spawnSync } from 'node:child_process';
import { resolve } from 'node:path';
const root = resolve(import.meta.dirname, '..');
for (const args of [['--prefix', 'promo-video', 'run', 'lint'], ['--prefix', 'promo-video', 'run', 'check:narration'], ['--prefix', 'promo-video', 'audit', '--audit-level=moderate']]) {
  const result = spawnSync(process.platform === 'win32' ? 'npm.cmd' : 'npm', args, { cwd: root, shell: process.platform === 'win32', windowsHide: true, stdio: 'inherit' });
  if (result.status !== 0) process.exit(result.status || 1);
}
