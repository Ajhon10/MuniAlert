import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtempSync, mkdirSync, writeFileSync, readFileSync, existsSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { resolve } from 'node:path';
import { stageHostinger } from '../package-hostinger.mjs';

test('Hostinger package keeps runtime secrets private and supplies the relocated public entry', () => {
  const root = mkdtempSync(resolve(tmpdir(), 'munialert-package-'));
  try {
    for (const directory of ['hazard-server/app', 'hazard-server/bootstrap/cache', 'hazard-server/config', 'hazard-server/database/seeders', 'hazard-server/resources', 'hazard-server/routes', 'hazard-server/public/app', 'hazard-server/storage/app/identity-private', 'hazard-client/dist-web', 'deployment/hostinger', 'documentation']) mkdirSync(resolve(root, directory), { recursive: true });
    for (const file of ['artisan', 'composer.json', 'composer.lock']) writeFileSync(resolve(root, 'hazard-server', file), '{}');
    for (const file of ['app/Example.php', 'bootstrap/app.php', 'config/app.php', 'public/.htaccess']) writeFileSync(resolve(root, 'hazard-server', file), 'source');
    for (const file of ['.env', 'database/seeders/BrowserSecuritySeeder.php', 'bootstrap/cache/config.php', 'storage/app/identity-private/private.enc', 'public/app/old-secret.js', 'config/service-account-private.json']) writeFileSync(resolve(root, 'hazard-server', file), 'SYNTHETIC_PRIVATE_CANARY');
    writeFileSync(resolve(root, 'hazard-client/dist-web/index.html'), 'fresh compiled app');
    writeFileSync(resolve(root, 'deployment/hostinger/public-index.php.example'), 'private sibling app entry');
    writeFileSync(resolve(root, 'deployment/hostinger/production.env.example'), 'APP_ENV=production');
    writeFileSync(resolve(root, 'documentation/HOSTINGER_WEB_CLOUD.md'), 'deployment instructions');
    const target = resolve(root, 'release');
    const files = stageHostinger(root, target);
    assert.ok(files.some(file => file.path === 'munialert/hazard-server/app/Example.php'));
    assert.equal(readFileSync(resolve(target, 'public_html/index.php'), 'utf8'), 'private sibling app entry');
    assert.equal(readFileSync(resolve(target, 'public_html/app/index.html'), 'utf8'), 'fresh compiled app');
    assert.equal(existsSync(resolve(target, 'munialert/hazard-server/.env')), false);
    assert.equal(files.some(file => readFileSync(resolve(target, file.path), 'utf8').includes('SYNTHETIC_PRIVATE_CANARY')), false);
    assert.throws(() => stageHostinger(root, target), /must be new/);
  } finally { assert.ok(root.startsWith(resolve(tmpdir(), 'munialert-package-'))); rmSync(root, { recursive: true, force: true }); }
});
