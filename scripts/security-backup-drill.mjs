// Synthetic localhost rehearsal only. Never reads/dumps the application schema.
import { spawnSync } from 'node:child_process';
import { createCipheriv, createDecipheriv, createHash, randomBytes, randomUUID } from 'node:crypto';
import { mkdirSync, writeFileSync, readFileSync, rmSync, realpathSync, lstatSync, existsSync } from 'node:fs';
import { resolve, join } from 'node:path';
import { pathToFileURL } from 'node:url';
import { php, phpEnv } from './php-runtime.mjs';
const root = resolve(import.meta.dirname, '..');
const schemas = ['municipal_hazards_backup_drill', 'municipal_hazards_restore_drill'];
export function assertDrillDatabase(name) { if (!schemas.includes(name)) throw Error('Only fixed synthetic drill schemas are allowed.'); return name; }
export function encryptBackup(bytes, key) {
  const iv = randomBytes(12); const cipher = createCipheriv('aes-256-gcm', key, iv);
  const body = Buffer.concat([cipher.update(bytes), cipher.final()]);
  return Buffer.concat([Buffer.from('MAB1'), iv, cipher.getAuthTag(), body]);
}
export function decryptBackup(bytes, key) {
  if (bytes.length < 32 || bytes.subarray(0, 4).toString() !== 'MAB1') throw Error('Invalid drill archive.');
  const decipher = createDecipheriv('aes-256-gcm', key, bytes.subarray(4, 16));
  decipher.setAuthTag(bytes.subarray(16, 32));
  return Buffer.concat([decipher.update(bytes.subarray(32)), decipher.final()]);
}
function run(binary, args, input) {
  const result = spawnSync(binary, args, { cwd: root, env: phpEnv(), input, encoding: 'utf8', maxBuffer: 8 * 1024 * 1024, windowsHide: true, timeout: 30000 });
  // Never forward CLI stderr, configuration, password or SQL payloads.
  if (result.status !== 0) {
    const category = /ERROR (\d+)/.exec(result.stderr || '')?.[0] || (result.stderr?.includes('PHP Fatal') ? 'PHP bootstrap error' : result.error?.code || 'command error');
    throw Error(`Synthetic backup drill ${binary.split(/[\\/]/).at(-1)} failed (${category}; exit ${result.status}); private output withheld.`);
  }
  return result.stdout.trim();
}
export function runDrill() {
  const started = Date.now(); const nonce = randomUUID(); const directory = resolve(root, '.cache', 'backup-drill-' + nonce);
  mkdirSync(directory, { recursive: false, mode: 0o700 });
  const created = [];
  let client; let defaults;
  function sql(statement, database) { return run(client, ['--defaults-extra-file=' + defaults, '--batch', '--skip-column-names', ...(database ? [assertDrillDatabase(database)] : []), '--execute', statement]); }
  try {
    const configuration = JSON.parse(run(php, ['-r', "require 'hazard-server/vendor/autoload.php'; $app=require 'hazard-server/bootstrap/app.php'; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); if (!$app->environment('local') || config('database.default')!=='mysql') exit(2); echo json_encode(Illuminate\\Support\\Facades\\DB::connection()->getConfig()); "]));
    if (!['127.0.0.1', 'localhost', '::1'].includes(configuration.host)) throw Error('Only localhost MySQL is allowed.');
    const binaryDirectory = process.env.MYSQL_CLIENT_DIR || (process.platform === 'win32' ? (existsSync('C:/Program Files/MySQL/MySQL Server 8.0/bin/mysql.exe') ? 'C:/Program Files/MySQL/MySQL Server 8.0/bin' : 'C:/xampp/mysql/bin') : '');
    client = binaryDirectory ? join(binaryDirectory, process.platform === 'win32' ? 'mysql.exe' : 'mysql') : 'mysql';
    const dump = binaryDirectory ? join(binaryDirectory, process.platform === 'win32' ? 'mysqldump.exe' : 'mysqldump') : 'mysqldump';
    const option = value => '"' + String(value ?? '').replaceAll('\\', '\\\\').replaceAll('"', '\\"').replaceAll('\n', '\\n').replaceAll('\r', '\\r') + '"';
    defaults = join(directory, 'client.cnf');
    writeFileSync(defaults, `[client]\nhost=${option(configuration.host)}\nport=${Number(configuration.port) || 3306}\nuser=${option(configuration.username)}\npassword=${option(configuration.password)}\n`, { mode: 0o600 });
    for (const name of schemas) {
      assertDrillDatabase(name);
      if (sql(`SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='${name}'`) !== '0') throw Error('A drill schema already exists. Refusing to overwrite it.');
    }
    for (const name of schemas) {
      sql(`CREATE DATABASE \`${name}\``); created.push(name);
      sql(`CREATE TABLE drill_guard (nonce VARCHAR(36) NOT NULL); INSERT INTO drill_guard VALUES ('${nonce}')`, name);
    }
    const [source, restored] = schemas;
    const evidence = Buffer.from('Synthetic evidence bytes; no municipal record or real photograph.');
    const evidenceHash = createHash('sha256').update(evidence).digest('hex');
    sql(`CREATE TABLE drill_reports (id INT PRIMARY KEY, reference VARCHAR(30)); INSERT INTO drill_reports VALUES (1,'SYNTHETIC-001'),(2,'SYNTHETIC-002'); CREATE TABLE drill_photos (id INT PRIMARY KEY, sha256 CHAR(64)); INSERT INTO drill_photos VALUES (1,'${evidenceHash}')`, source);
    const dumpSql = run(dump, ['--defaults-extra-file=' + defaults, '--single-transaction', '--skip-lock-tables', '--no-tablespaces', '--skip-comments', '--skip-add-locks', '--skip-disable-keys', source]);
    const key = randomBytes(32); // Ephemeral drill key, never written/logged.
    const archive = encryptBackup(Buffer.from(JSON.stringify({ sql: dumpSql, evidence: evidence.toString('base64') })), key);
    writeFileSync(join(directory, 'synthetic-backup.enc'), archive, { mode: 0o600 });
    const tampered = Buffer.from(archive); tampered[tampered.length - 1] ^= 1;
    let tamperRejected = false;
    try { decryptBackup(tampered, key); } catch { tamperRejected = true; }
    if (!tamperRejected) throw Error('Archive authentication failed.');
    const recovered = JSON.parse(decryptBackup(readFileSync(join(directory, 'synthetic-backup.enc')), key).toString());
    run(client, ['--defaults-extra-file=' + defaults, restored], recovered.sql);
    const rows = Number(sql('SELECT COUNT(*) FROM drill_reports', restored));
    const restoredHash = createHash('sha256').update(Buffer.from(recovered.evidence, 'base64')).digest('hex');
    if (rows !== 2 || restoredHash !== evidenceHash || sql('SELECT sha256 FROM drill_photos WHERE id=1', restored) !== evidenceHash) throw Error('Synthetic restore verification failed.');
    return { passed: true, scope: 'synthetic localhost MySQL rehearsal only', restoredReports: rows, evidenceHashMatched: true, encryptedArchiveAuthenticated: true, tamperRejected, productionDatabaseRead: false, productionBackupAccepted: false, durationMs: Date.now() - started };
  } finally {
    for (const name of created.reverse()) {
      if (sql('SELECT nonce FROM drill_guard', name) !== nonce) throw Error('Drill ownership marker mismatch; database retained for review.');
      sql(`DROP DATABASE \`${assertDrillDatabase(name)}\``);
    }
    const absolute = realpathSync(directory);
    if (lstatSync(directory).isSymbolicLink() || absolute !== directory || !absolute.startsWith(resolve(root, '.cache') + (process.platform === 'win32' ? '\\' : '/')) || !/^backup-drill-[a-f0-9-]{36}$/.test(absolute.split(/[\\/]/).at(-1))) throw Error('Unsafe drill cleanup target.');
    rmSync(absolute, { recursive: true });
  }
}
if (process.argv[1] && import.meta.url === pathToFileURL(resolve(process.argv[1])).href) {
  try { console.log(JSON.stringify(runDrill(), null, 2)); }
  catch (error) { console.error(error.message); process.exitCode = 1; }
}
