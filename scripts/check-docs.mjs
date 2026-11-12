import { php } from './php-runtime.mjs';
import { readFileSync, readdirSync, existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';
const root = resolve(import.meta.dirname, '..'); const docs = resolve(root, 'documentation');
const names = readdirSync(docs).filter(name => /^\d\d-.*\.md$/.test(name));
if (names.length !== 10) throw Error('The ten capstone source attachments are required.');
const files = [resolve(root, 'README.md'), resolve(root, 'TASKS.md'), resolve(root, 'MEMORY.md'), resolve(root, 'AGENTS.md'), resolve(docs, 'README.md'), ...names.map(name => resolve(docs, name))];
for (const file of files) {
  const text = readFileSync(file, 'utf8');
  for (const match of text.matchAll(/\[[^\]]*\]\(([^)]+)\)/g)) { const target = match[1].split('#')[0]; if (!target || /^(https?:|mailto:)/.test(target)) continue; if (!existsSync(resolve(dirname(file), decodeURIComponent(target)))) throw Error(`Broken documentation link in ${file}: ${target}`); }
}
const requirements = readFileSync(resolve(docs, '01-project-overview.md'), 'utf8'); const traceability = readFileSync(resolve(docs, '10-traceability-and-acceptance.md'), 'utf8');
for (let id = 1; id <= 31; id++) { const key = `FR-${String(id).padStart(2, '0')}`; if (!requirements.includes(key) || !traceability.includes(key)) throw Error(`Missing requirement or traceability entry: ${key}`); }
const municipality = JSON.parse(readFileSync(resolve(root, 'hazard-client/src/config/municipality.json'), 'utf8'));
const serverScope = spawnSync(php, ['-r', 'echo json_encode(require "hazard-server/config/municipality.php");'], { cwd: root, encoding: 'utf8' });
if (serverScope.status !== 0 || JSON.stringify(JSON.parse(serverScope.stdout).barangays) !== JSON.stringify(municipality.barangays)) throw Error('Client/server Basista coverage differs.');
const boundaryDir = resolve(root, 'hazard-client/public/boundaries');
const features = JSON.parse(readFileSync(resolve(boundaryDir, 'basista-barangays.geojson'), 'utf8')).features;
if (features.length !== 13 || JSON.stringify(features.map(feature => feature.properties.name).sort()) !== JSON.stringify([...municipality.barangays].sort())) throw Error('Basista boundary names do not match the 13 approved barangays.');
for (const output of JSON.parse(readFileSync(resolve(boundaryDir, 'provenance.json'), 'utf8')).outputs) if (createHash('sha256').update(readFileSync(resolve(boundaryDir, output.file))).digest('hex') !== output.sha256) throw Error(`Boundary provenance mismatch: ${output.file}`);
const routeResult = spawnSync(php, ['hazard-server/artisan', 'route:list', '--json', '--path=api'], { cwd: root, encoding: 'utf8' });
if (routeResult.status !== 0) throw Error('Cannot verify the API documentation against Laravel routes.');
const apiText = readFileSync(resolve(docs, '04-api-reference.md'), 'utf8');
for (const route of JSON.parse(routeResult.stdout)) if (!apiText.includes('`/' + route.uri + '`')) throw Error(`Undocumented API route: ${route.uri}`);
const hook = readFileSync(resolve(root, '.githooks/pre-push'), 'utf8');
if (!hook.includes('npm run verify') || !hook.includes('check-publication.mjs --clean')) throw Error('The workspace pre-push verification/clean-publication hook is missing.');
const generated = spawnSync(process.execPath, ['scripts/build-docs.mjs', '--check'], { cwd: root, stdio: 'inherit' });
if (generated.status !== 0) process.exit(1);
console.log(`Documentation verified: ${files.length} source files, 31 requirements, Basista scope/boundary provenance, all API routes and generated attachments.`);
