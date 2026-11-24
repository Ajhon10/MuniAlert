import { existsSync, mkdirSync, readFileSync, readdirSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import MarkdownIt from 'markdown-it';
import { php, phpEnv } from './php-runtime.mjs';

const root = resolve(import.meta.dirname, '..');
const dir = resolve(root, 'documentation/capstone');
const sources = readdirSync(dir).filter(file => /^\d\d-.*\.md$/.test(file)).sort();
if (sources.length !== 7) throw Error('Expected front matter, five chapters and references/appendices.');
const md = new MarkdownIt({ html: false, linkify: true, xhtmlOut: true });
const documents = sources.map(file => ({ file, text: readFileSync(resolve(dir, file), 'utf8') }));
const combined = documents.map(document => document.text).join('\n\n---\n\n');
const toc = documents.slice(1).map((document, index) => `<li><a href="#part-${index + 1}">${md.utils.escapeHtml(document.text.split('\n')[0].replace(/^# /, ''))}</a></li>`).join('');
const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><title>MuniAlert — Editable Capstone Chapters 1–5</title><style>
@page{size:A4;margin:25.4mm}*{box-sizing:border-box}body{font:12pt/1.5 'Times New Roman',serif;color:#111;background:#eee;margin:0}main{max-width:210mm;margin:20px auto;padding:25.4mm;background:white}section{break-before:page}section:first-child{break-before:auto}.cover{min-height:225mm}.contents{break-before:page;break-after:page}h1,h2,h3{line-height:1.3;break-after:avoid}h1{font-size:16pt;text-align:center;margin-bottom:25pt}h2{font-size:14pt;margin-top:22pt}h3{font-size:12pt}p{text-align:justify;margin:0 0 12pt}a{color:#234b73;overflow-wrap:anywhere}table{border-collapse:collapse;width:100%;font-size:10pt;margin:14pt 0}td,th{border:1px solid #888;padding:6pt;text-align:left;vertical-align:top}th{background:#eee}tr{break-inside:avoid}li{margin-bottom:5pt}code{font-size:10pt;overflow-wrap:anywhere}@media print{body{background:white}main{margin:0;padding:0;max-width:none}a{color:inherit}}@media(max-width:700px){main{margin:0;padding:20px}table{font-size:9pt}}
</style></head><body><main>${documents.map((document, index) => `<section id="part-${index}" class="${index === 0 ? 'cover' : 'chapter'}">${md.render(document.text)}</section>${index === 0 ? `<nav class="contents"><h1>CONTENTS</h1><p>Editable draft. Page numbers are available in the Word table of contents after updating fields.</p><ol>${toc}</ol></nav>` : ''}`).join('')}</main></body></html>`;
const htmlPath = resolve(dir, 'MUNIALERT-CAPSTONE.html');
const sourceHash = createHash('sha256').update(combined).digest('hex');
const artifacts = ['MANUSCRIPT.md', 'MUNIALERT-CAPSTONE.html', 'MUNIALERT-CAPSTONE.docx', 'MUNIALERT-CAPSTONE.pdf'];
if (process.argv.includes('--check')) {
  const manifest = JSON.parse(readFileSync(resolve(dir, 'artifacts.json'), 'utf8'));
  if (manifest.sourceHash !== sourceHash) throw Error('Capstone sources changed; run npm run docs:capstone.');
  for (const file of artifacts) if (createHash('sha256').update(readFileSync(resolve(dir, file))).digest('hex') !== manifest.files[file]) throw Error(`Capstone artifact differs: ${file}`);
  console.log('Capstone sources and DOCX/PDF/HTML/Markdown artifacts are consistent.');
  process.exit(0);
}
writeFileSync(resolve(dir, 'MANUSCRIPT.md'), combined);
writeFileSync(htmlPath, html);
mkdirSync(resolve(root, '.cache'), { recursive: true });
const input = resolve(root, '.cache/capstone-document.json');
writeFileSync(input, JSON.stringify(documents.map(document => ({ html: md.render(document.text) }))));
const word = spawnSync(php, [resolve(root, 'scripts/render-capstone-docx.php'), input, resolve(dir, 'MUNIALERT-CAPSTONE.docx')], { cwd: root, env: phpEnv(), encoding: 'utf8', windowsHide: true });
if (word.status !== 0 || word.stderr.trim()) throw Error(`Word export failed: ${word.stderr || word.stdout}`);
if (!process.env.PLAYWRIGHT_BROWSERS_PATH && existsSync(resolve(root, '.cache/playwright'))) process.env.PLAYWRIGHT_BROWSERS_PATH = resolve(root, '.cache/playwright');
const { chromium } = await import('@playwright/test');
const browser = await chromium.launch();
try {
  const page = await browser.newPage();
  await page.goto(pathToFileURL(htmlPath).href); await page.evaluate(() => document.fonts.ready);
  await page.pdf({ path: resolve(dir, 'MUNIALERT-CAPSTONE.pdf'), format: 'A4', preferCSSPageSize: true, printBackground: true, displayHeaderFooter: true, headerTemplate: '<span></span>', footerTemplate: '<div style="font:9px Times New Roman;width:100%;text-align:center">MuniAlert · Capstone draft · <span class="pageNumber"></span></div>' });
} finally { await browser.close(); }
writeFileSync(resolve(dir, 'artifacts.json'), JSON.stringify({ sourceHash, files: Object.fromEntries(artifacts.map(file => [file, createHash('sha256').update(readFileSync(resolve(dir, file))).digest('hex')])) }, null, 2) + '\n');
console.log(`Generated editable DOCX, PDF, HTML and consolidated Markdown from ${sources.length} sources (${combined.split(/\s+/).length} words). Academic details and evaluation fields remain editable.`);
