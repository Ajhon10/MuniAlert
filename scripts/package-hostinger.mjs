import { cpSync, existsSync, lstatSync, mkdirSync, readFileSync, readdirSync, writeFileSync } from 'node:fs';
import { basename, join, relative, resolve } from 'node:path';
import { createHash, randomUUID } from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

export function stageHostinger(root, target) {
  const server = resolve(root, 'hazard-server');
  const privateApp = resolve(target, 'munialert/hazard-server');
  const publicRoot = resolve(target, 'public_html');
  if (!existsSync(resolve(root, 'hazard-client/dist-web/index.html'))) throw Error('Run npm run build:web first.');
  if (existsSync(target)) throw Error('Release destination must be new; existing releases are preserved.');
  const safe = path => {
    const name = basename(path);
    const segments = relative(server, path).split(/[\\/]/);
    return !lstatSync(path).isSymbolicLink() && !name.startsWith('.env')
      && !/^Browser.*Seeder\.php$/.test(name)
      && !segments.some(part => ['vendor', 'node_modules', 'storage', 'tests', '.git', 'cache', 'app'].includes(part) && (part !== 'app' || segments[0] === 'public'))
      && !/\.(?:sqlite(?:-journal)?|log|pem|key|p12|pfx|jks|keystore)$/i.test(name)
      && !/^(?:auth\.json|google-services\.json|.*service-account.*\.json)$/i.test(name);
  };
  mkdirSync(privateApp, { recursive: true }); mkdirSync(publicRoot, { recursive: true });
  // Explicit application sources only. Never traverse workstation runtime data.
  for (const item of ['app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'public', 'artisan', 'composer.json', 'composer.lock']) {
    const source = resolve(server, item);
    cpSync(source, resolve(privateApp, item), { recursive: true, filter: safe });
  }
  for (const directory of ['bootstrap/cache', 'storage/app/private/evidence', 'storage/app/identity-private', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs']) mkdirSync(resolve(privateApp, directory), { recursive: true });
  const web = resolve(root, 'hazard-client/dist-web');
  cpSync(web, resolve(publicRoot, 'app'), { recursive: true });
  cpSync(web, resolve(privateApp, 'public/app'), { recursive: true });
  cpSync(resolve(root, 'deployment/hostinger/public-index.php.example'), resolve(publicRoot, 'index.php'));
  cpSync(resolve(server, 'public/.htaccess'), resolve(publicRoot, '.htaccess'));
  cpSync(resolve(root, 'deployment/hostinger/production.env.example'), resolve(privateApp, '.env.example'));
  cpSync(resolve(root, 'documentation/HOSTINGER_WEB_CLOUD.md'), resolve(target, 'DEPLOYMENT.md'));
  const manifest = [];
  function collect(dir) {
    for (const entry of readdirSync(dir, { withFileTypes: true })) {
      const path = join(dir, entry.name);
      if (entry.isDirectory()) collect(path);
      else manifest.push({ path: relative(target, path).replaceAll('\\', '/'), sha256: createHash('sha256').update(readFileSync(path)).digest('hex') });
    }
  }
  collect(target);
  writeFileSync(resolve(target, 'release-manifest.json'), JSON.stringify({ generatedAt: new Date().toISOString(), includesVendor: false, requiresComposerInstall: true, deployed: false, files: manifest }, null, 2));
  return manifest;
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const root = resolve(import.meta.dirname, '..');
  const target = resolve(root, '.cache/releases', `hostinger-${randomUUID()}`);
  const manifest = stageHostinger(root, target);
  const archive = target + '.zip';
  const tar = process.platform === 'win32' ? resolve(process.env.SystemRoot || 'C:/Windows', 'System32/tar.exe') : 'tar';
  const result = spawnSync(tar, ['-a', '-cf', archive, '-C', target, '.'], { windowsHide: true, encoding: 'utf8' });
  if (result.status !== 0) throw Error('Archive creation failed. The prepared release directory remains available.');
  console.log(`Prepared ${manifest.length} files: ${archive}\nNo .env, uploads, credentials, logs or vendor included. Run Composer on Hostinger and follow DEPLOYMENT.md. This package is not a deployment or release approval.`);
}
