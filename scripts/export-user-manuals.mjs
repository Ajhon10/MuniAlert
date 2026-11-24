import { readFileSync, writeFileSync, existsSync, mkdirSync } from 'node:fs';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
import { createRequire } from 'node:module';
import { runInNewContext } from 'node:vm';

const root = resolve(import.meta.dirname, '..');
const clientRequire = createRequire(resolve(root, 'hazard-client/package.json'));
const ts = clientRequire('typescript');
const identity = JSON.parse(readFileSync(resolve(root, 'hazard-client/src/config/identity.json'), 'utf8'));
// Export the actual bundled instructions; do not maintain a second manual.
const source = readFileSync(resolve(root, 'hazard-client/src/data/help.ts'), 'utf8');
const compiled = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022, esModuleInterop: true } }).outputText;
const exports = {};
runInNewContext(compiled, { exports, require(name) { if (name !== '../config/identity.json') throw Error('Review the new manual import before export.'); return identity; } }, { timeout: 1000 });
if (!Array.isArray(exports.manualSections)) throw Error('Bundled manual sections are unavailable.');
const escape = text => String(text).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
if (!process.env.PLAYWRIGHT_BROWSERS_PATH && existsSync(resolve(root, '.cache/playwright'))) process.env.PLAYWRIGHT_BROWSERS_PATH = resolve(root, '.cache/playwright');
const { chromium } = await import('@playwright/test');
const browser = await chromium.launch();
try {
  mkdirSync(resolve(root, '.cache/manuals'), { recursive: true });
  for (const audience of ['citizen', 'admin']) {
    const sections = exports.manualSections.filter(section => ['all', audience].includes(section.audience));
    const role = audience === 'admin' ? 'Administrator' : 'Citizen';
    const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><title>${escape(identity.name)} — ${role} manual</title><style>@page{size:A4;margin:22mm}body{font:11pt/1.6 Arial,sans-serif;color:#173c31}h1{font-size:24pt}h2{font-size:15pt;break-after:avoid}section{margin-top:24pt}li{margin-bottom:8pt}p{margin:8pt 0}.note{padding:12pt;border-left:3pt solid #347860;background:#f1f6f3}footer{font-size:9pt;color:#4f625b}a{color:inherit}</style></head><body><header><h1>${escape(identity.name)}</h1><h2>${role} user manual</h2><p>${escape(identity.capstoneTitle)}</p><p>Local prototype instructions. An online report does not guarantee emergency dispatch. Use approved local emergency contacts for immediate danger.</p></header>${sections.map(section => `<section><h2>${escape(section.title)}</h2><p>${escape(section.summary)}</p><ol>${section.steps.map(step => `<li>${escape(step)}</li>`).join('')}</ol>${section.note ? `<p class="note">${escape(section.note)}</p>` : ''}</section>`).join('')}<footer>Generated from the same bundled instructions used in the application. Hosting, physical-device and municipal acceptance remain separate.</footer></body></html>`;
    const input = resolve(root, `.cache/manuals/${audience}.html`);
    writeFileSync(input, html);
    const page = await browser.newPage();
    try {
      await page.goto(pathToFileURL(input).href); await page.evaluate(() => document.fonts.ready);
      await page.pdf({ path: resolve(root, `documentation/MUNIALERT-${audience === 'admin' ? 'ADMIN' : 'CITIZEN'}-MANUAL.pdf`), format: 'A4', preferCSSPageSize: true, printBackground: true });
    } finally { await page.close(); }
    console.log(`Exported MuniAlert ${role.toLowerCase()} PDF from ${sections.length} bundled topics.`);
  }
} finally { await browser.close(); }
