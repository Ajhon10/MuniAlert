import { spawnSync } from 'node:child_process';
import { resolve } from 'node:path';
import { php, phpEnv } from './php-runtime.mjs';
const server = resolve(import.meta.dirname, '../hazard-server');
const result = spawnSync(php, ['vendor/bin/phpstan', 'analyse', '--no-progress', '--memory-limit=512M'], {
  cwd: server, stdio: 'inherit', windowsHide: true,
  env: phpEnv({ APP_ENV: 'testing', APP_DEBUG: 'false', DB_CONNECTION: 'sqlite', DB_DATABASE: ':memory:', APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=' }),
});
process.exitCode = result.status ?? 1;
