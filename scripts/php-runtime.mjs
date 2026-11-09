import { existsSync } from 'node:fs';
import { dirname, delimiter } from 'node:path';

export const php = process.platform === 'win32' ? 'C:/php85/php.exe' : (process.env.BANTAY_PHP_BINARY || 'php');
if (process.platform === 'win32' && !existsSync(php)) throw Error('BANTAY requires the workstation PHP at C:/php85/php.exe.');
export function phpEnv(extra = {}) {
  const env = { ...process.env, ...extra };
  if (process.platform === 'win32') {
    const key = Object.keys(env).find(key => key.toLowerCase() === 'path') || 'PATH';
    env[key] = dirname(php) + delimiter + (env[key] || '');
  }
  return env;
}
