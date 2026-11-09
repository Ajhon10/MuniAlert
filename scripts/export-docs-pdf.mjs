import { existsSync } from 'node:fs';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
const root = resolve(import.meta.dirname, '..');
if (!process.env.PLAYWRIGHT_BROWSERS_PATH && existsSync(resolve(root, '.cache/playwright'))) process.env.PLAYWRIGHT_BROWSERS_PATH = resolve(root, '.cache/playwright');
const { chromium } = await import('@playwright/test');
const browser = await chromium.launch();
try {
  const page = await browser.newPage();
  await page.goto(pathToFileURL(resolve(root, 'documentation/CAPSTONE-TECHNICAL-DOCUMENTATION.html')).href);
  await page.evaluate(() => document.fonts.ready);
  await page.pdf({ path: resolve(root, 'documentation/CAPSTONE-TECHNICAL-DOCUMENTATION.pdf'), format: 'A4', preferCSSPageSize: true, printBackground: true, displayHeaderFooter: true, headerTemplate: '<span></span>', footerTemplate: '<div style="font:8px Arial;width:100%;text-align:center;color:#777">MuniAlert · Technical appendix · <span class="pageNumber"></span> / <span class="totalPages"></span></div>' });
  console.log('Exported the capstone technical appendix PDF. Regenerate it after changing source documentation.');
} finally { await browser.close(); }
