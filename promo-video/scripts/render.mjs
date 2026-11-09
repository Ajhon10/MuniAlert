import { spawnSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const cli = resolve(root, 'node_modules/@remotion/cli/remotion-cli.js');
const browser = process.env.MUNIALERT_BROWSER_EXECUTABLE;
const browserArgs = browser ? [`--browser-executable=${browser}`] : [];
mkdirSync(resolve(root, 'out'), { recursive: true });
function run(args) {
  const result = spawnSync(process.execPath, [cli, ...args, ...browserArgs], { cwd: root, windowsHide: true, stdio: 'inherit' });
  if (result.status !== 0) process.exit(result.status || 1);
}
if (process.argv.includes('--review')) {
  for (const frame of [100, 330, 480, 580, 850, 1150, 1430, 1690]) {
    run(['still', 'MuniAlertPortrait', `out/review-portrait/element-${String(frame).padStart(4, '0')}.png`, `--frame=${frame}`, '--scale=0.5']);
    run(['still', 'MuniAlertLandscape', `out/review-landscape/element-${String(frame).padStart(4, '0')}.png`, `--frame=${frame}`, '--scale=0.5']);
  }
} else if (process.argv.includes('--poster')) {
  run(['still', 'MuniAlertPortrait', 'out/MuniAlert-poster.png', '--frame=100']);
} else {
  if (!process.argv.includes('--landscape')) run(['render', 'MuniAlertPortrait', 'out/MuniAlert-portrait.mp4', '--codec=h264', '--crf=20', '--concurrency=2']);
  if (!process.argv.includes('--portrait')) run(['render', 'MuniAlertLandscape', 'out/MuniAlert-landscape.mp4', '--codec=h264', '--crf=20', '--concurrency=2']);
}
