import { test, expect } from './fixtures';

test('dashboard places content within phone, tablet, landscape and desktop viewports', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('/?demo=1');
  await expect(page.locator('.metric-value').first()).toContainText('06');
  for (const [width, height] of [[1920, 1080], [1366, 768], [1024, 768], [768, 1024], [844, 390], [390, 844], [375, 812], [320, 740]]) {
    await page.setViewportSize({ width, height });
    await page.evaluate(() => document.fonts.ready);
    await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    for (const selector of ['.page-heading', '.metrics', '.filter-bar', '.map-panel', '.reports-panel']) {
      const bounds = await page.locator(selector).boundingBox();
      expect(bounds!.x).toBeGreaterThanOrEqual(0);
      expect(bounds!.x + bounds!.width).toBeLessThanOrEqual(width + 1);
    }
    if (width < 640) {
      await expect(page.locator('.table-scroll')).toBeHidden();
      await expect(page.locator('.report-summary')).toHaveCount(5);
      const button = page.locator('.report-summary').first();
      await button.click();
      await expect(page.getByRole('dialog', { name: 'Hazard report', exact: true })).toBeVisible();
      await page.getByRole('button', { name: 'Close dialog' }).click();
    } else {
      await expect(page.locator('.table-scroll')).toBeVisible();
      await expect(page.locator('.mobile-report-list')).toBeHidden();
    }
  }
  await page.locator('main').focus();
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.screenshot({ path: 'test-results/responsive-320px.png', fullPage: true });
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.evaluate(() => window.scrollTo(0, 0));
  await expect.poll(() => page.locator('.sidebar').evaluate(element => element.getBoundingClientRect().x)).toBe(0);
  await page.screenshot({ path: 'test-results/responsive-desktop.png', fullPage: true });
  expect(errors).toEqual([]);
});

test('compact navigation supports keyboard opening and escape; long headings wrap', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 812 });
  await page.goto('/?demo=1');
  await page.getByRole('button', { name: 'More navigation' }).click();
  await expect(page.getByRole('button', { name: 'Dashboard', exact: true })).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(page.getByRole('button', { name: 'More navigation' })).toBeFocused();
  await page.getByRole('button', { name: 'More navigation' }).click();
  await page.getByRole('button', { name: 'Verified news', exact: true }).click();
  await expect(page.locator('h1')).toHaveText('Verified community news');
  await page.evaluate(() => document.fonts.ready);
  const heading = await page.locator('h1').boundingBox();
  expect(heading!.x + heading!.width).toBeLessThanOrEqual(375);
  await expect(page.getByRole('button', { name: 'More navigation' })).toHaveAttribute('aria-expanded', 'false');
});
