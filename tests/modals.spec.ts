import { test, expect, continueReport } from './fixtures';
import type { Locator } from '@playwright/test';

async function fixedChrome(dialog: Locator, bodySelector = '.modal-body', headerSelector = '.modal-header', footerSelector = '.modal-footer') {
  const body = dialog.locator(bodySelector);
  const header = dialog.locator(headerSelector);
  const footer = dialog.locator(footerSelector);
  await body.evaluate(element => { element.scrollTop = 0; });
  const before = { header: await header.boundingBox(), footer: await footer.boundingBox() };
  await body.evaluate(element => { element.scrollTop = element.scrollHeight; });
  const after = { header: await header.boundingBox(), footer: await footer.boundingBox(), body: await body.boundingBox() };
  expect(after.header!.y).toBeCloseTo(before.header!.y, 0);
  expect(after.footer!.y).toBeCloseTo(before.footer!.y, 0);
  expect(after.body!.y).toBeGreaterThanOrEqual(after.header!.y + after.header!.height - 1);
  expect(after.body!.y + after.body!.height).toBeLessThanOrEqual(after.footer!.y + 1);
  expect(after.footer!.y + after.footer!.height).toBeLessThanOrEqual(await dialog.evaluate(() => innerHeight) + 1);
  return body;
}

test('report forms keep header and submit visible while only the body scrolls, including a short phone viewport', async ({ page }) => {
  await page.goto('/?demo=1');
  await page.getByRole('button', { name: 'Report a hazard', exact: true }).click(); await continueReport(page);
  const dialog = page.getByRole('dialog', { name: 'Report a hazard', exact: true });
  await dialog.getByRole('button', { name: 'Fallen tree', exact: true }).click();
  const image = await page.evaluate(() => { const canvas = document.createElement('canvas'); canvas.width = 600; canvas.height = 400; return canvas.toDataURL('image/png').split(',')[1]; });
  await dialog.getByLabel('Add hazard photos').setInputFiles({ name: 'fictional-test.png', mimeType: 'image/png', buffer: Buffer.from(image, 'base64') });
  await expect(dialog.locator('.photo-preview')).toHaveCount(1);
  for (const [width, height] of [[1280, 800], [390, 844], [320, 740], [844, 390], [390, 480]]) {
    await page.setViewportSize({ width, height });
    const body = await fixedChrome(dialog);
    expect(await body.evaluate(element => element.scrollTop)).toBeGreaterThan(0);
    expect(await dialog.evaluate(element => element.scrollTop)).toBe(0);
    await expect(dialog.getByRole('button', { name: 'Submit hazard report' })).toBeVisible();
    expect(await dialog.getByRole('button', { name: 'Submit hazard report' }).evaluate((element: HTMLButtonElement) => !!element.form?.classList.contains('report-form'))).toBe(true);
    await expect.poll(() => dialog.evaluate(element => element.scrollWidth <= element.clientWidth)).toBe(true);
  }
  await page.setViewportSize({ width: 390, height: 844 });
  await page.screenshot({ path: 'test-results/modal-report-fixed-actions.png' });
  await dialog.getByRole('button', { name: 'Close dialog' }).click();
  const confirmation = page.getByRole('dialog', { name: 'Discard this report?' });
  await expect(confirmation).toBeVisible();
  await fixedChrome(confirmation);
  await confirmation.getByRole('button', { name: 'Close dialog', exact: true }).click();
  await expect(dialog).toBeVisible();
  expect(await page.evaluate(() => document.body.style.overflow)).toBe('hidden');
  await dialog.getByRole('button', { name: 'Close dialog' }).click();
  await page.getByRole('dialog', { name: 'Discard this report?' }).getByRole('button', { name: 'Discard report' }).click();
  await expect(dialog).not.toBeVisible();
  expect(await page.evaluate(() => document.body.style.overflow)).not.toBe('hidden');
});

test('sign-in and report details share body padding and optional action footers with server field validation', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 600 });
  await page.goto('/');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community' });
  await auth.getByRole('button', { name: 'Create an account', exact: true }).click();
  await fixedChrome(auth);
  await auth.getByRole('button', { name: 'Create your account', exact: true }).click();
  await expect(auth.getByLabel('Full name')).toBeFocused();
  const name = await auth.getByLabel('Full name').boundingBox();
  const body = await auth.locator('.modal-body').boundingBox();
  expect(name!.y).toBeGreaterThanOrEqual(body!.y - 1);
  expect(name!.y + name!.height).toBeLessThanOrEqual(body!.y + body!.height + 1);
  await auth.getByRole('button', { name: 'Close dialog' }).click();
  await page.goto('/?demo=1');
  await page.locator('.report-summary').first().click();
  const detail = page.getByRole('dialog', { name: 'Hazard report', exact: true });
  await expect(detail.locator('.modal-footer')).toHaveCount(0);
  await expect(detail.getByRole('button', { name: 'Close dialog', exact: true })).toBeVisible();
  await detail.getByRole('button', { name: 'Close dialog', exact: true }).click();
  expect(await page.evaluate(() => document.body.style.overflow)).not.toBe('hidden');
});

test('feature tour keeps its title and navigation visible with a scrolling explanation', async ({ page }) => {
  await page.setViewportSize({ width: 667, height: 375 });
  await page.goto('/?demo=1&view=guide');
  await page.getByRole('button', { name: 'Start feature tour', exact: true }).click();
  const tour = page.getByRole('dialog', { name: 'Feature tour' });
  await page.addStyleTag({ content: '.tour-body p { font-size: 32px; }' });
  const body = await fixedChrome(tour, '.tour-body', '.tour-header', '.tour-footer');
  expect(await body.evaluate(element => element.scrollTop)).toBeGreaterThan(0);
  await page.setViewportSize({ width: 320, height: 740 });
  await page.addStyleTag({ content: '.tour-header h2 { font-size: 44px; } .tour-sheet-top > span { font-size: 28px; } .tour-footer .button, .tour-skip { font-size: 28px; white-space: normal; }' });
  await fixedChrome(tour, '.tour-body', '.tour-header', '.tour-footer');
  expect(await body.evaluate(element => element.clientHeight)).toBeGreaterThan(48);
  await tour.getByRole('button', { name: 'Next', exact: true }).click();
  await expect(tour.getByRole('heading', { name: 'Start a report', exact: true })).toBeVisible();
  await tour.getByRole('button', { name: 'Close feature tour' }).click();
  expect(await page.evaluate(() => document.body.style.overflow)).not.toBe('hidden');
});
