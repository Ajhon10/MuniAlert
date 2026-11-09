import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
import MarkdownIt from 'markdown-it';

const root = resolve(import.meta.dirname, '..');
const directory = resolve(root, 'documentation');
const basename = 'INTERVIEW_ALIGNMENT_2026-11-08';
const markdown = new MarkdownIt({ html: false });
const content = markdown.render(readFileSync(resolve(directory, `${basename}.md`), 'utf8'))
  .replace(/<pre><code class="language-mermaid">[\s\S]*?<\/code><\/pre>/g, '<img src="diagrams/architecture.svg" alt="BANTAY Android, API and private evidence architecture">');
const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>BANTAY interview and Android review</title><style>
*{box-sizing:border-box}body{margin:0;background:#eef4f1;color:#263b35;font:16px/1.65 system-ui,sans-serif}main{max-width:1100px;margin:32px auto;padding:40px;background:white;border:1px solid #c9d8d0;border-radius:16px}h1{font-size:30px;line-height:1.3}h2{font-size:23px;line-height:1.4;margin-top:36px}a{color:#1d644e;overflow-wrap:anywhere}p,td{overflow-wrap:anywhere}table{border-collapse:collapse;width:100%;margin:20px 0;font-size:14px}td,th{border:1px solid #c7d7cd;padding:12px;vertical-align:top;text-align:left}th{background:#edf5f0}code{font-size:.9em}img{max-width:100%;height:auto}li{margin-block:10px}.tools{padding:16px;background:#1c5441;color:white;display:flex;justify-content:space-between;gap:16px}.tools button{font:inherit;padding:10px 18px;border:0;background:white;color:#1c5441;border-radius:8px;min-height:48px}
@media(max-width:639px){main{margin:0;border:0;border-radius:0;padding:24px 16px}h1{font-size:27px}table,tbody,tr,td{display:block}thead{display:none}tr{border:1px solid #b9cdbf;border-radius:12px;margin:16px 0;padding:8px;background:#fafcfb}td{border:0;padding:8px;font-size:16px}td::before{display:block;content:attr(data-label);font-size:14px;font-weight:700;color:#3b6652;margin-bottom:4px}}
@page{size:A4;margin:17mm}@media print{body{background:white;font:11px/1.6 Arial,sans-serif}main{padding:0;margin:0;border:0}h1{font-size:24px}h2{font-size:17px;break-after:avoid}table{font-size:9px}td,th{padding:7px}tr,img{break-inside:avoid}.tools{display:none}a{color:inherit}img{max-height:200mm}}
</style></head><body><div class="tools"><span>BANTAY · Source review · 8 November 2026</span><button onclick="window.print()">Print / Save PDF</button></div><main>${content}</main><script>for(const table of document.querySelectorAll('table')){const labels=[...table.querySelectorAll('thead th')].map(cell=>cell.textContent);for(const row of table.querySelectorAll('tbody tr'))[...row.children].forEach((cell,i)=>cell.dataset.label=labels[i]||'');}</script></body></html>`;
writeFileSync(resolve(directory, `${basename}.html`), html);
if (!process.env.PLAYWRIGHT_BROWSERS_PATH && existsSync(resolve(root, '.cache/playwright'))) process.env.PLAYWRIGHT_BROWSERS_PATH = resolve(root, '.cache/playwright');
const { chromium } = await import('@playwright/test');
const browser = await chromium.launch();
try {
  const page = await browser.newPage();
  await page.goto(pathToFileURL(resolve(directory, `${basename}.html`)).href);
  await page.evaluate(() => document.fonts.ready);
  await page.pdf({ path: resolve(directory, `${basename}.pdf`), format: 'A4', printBackground: true, preferCSSPageSize: true });
  await page.setViewportSize({ width: 360, height: 800 });
  if (!await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)) throw Error('Review report overflows the phone viewport.');
  console.log('Exported phone-readable HTML and PDF interview review.');
} finally { await browser.close(); }
