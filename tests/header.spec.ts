import { test, expect } from './fixtures';

test('compact header uses bottom More, preserves reading space and remains legible over content', async ({ page }) => {
  await page.goto('/?demo=1&view=guide');
  const header = page.locator('.topbar');
  await expect(header.locator('.mobile-menu')).toHaveCount(0);
  for (const width of [320, 390, 709, 768, 1023]) {
    await page.setViewportSize({ width, height: 844 });
    await expect(page.getByRole('button', { name: 'More navigation', exact: true })).toBeVisible();
    await expect(header.locator('.ph-clock')).toBeHidden();
    await expect(header.locator('.breadcrumb > span')).toHaveText('MuniAlert');
    const brand = await header.locator('.breadcrumb').boundingBox();
    const actions = await header.locator('.top-actions').boundingBox();
    expect(brand!.x + brand!.width).toBeLessThanOrEqual(actions!.x);
    const bounds = await header.boundingBox();
    expect(bounds!.x).toBeGreaterThanOrEqual(0);
    expect(bounds!.x + bounds!.width).toBeLessThanOrEqual(width);
    await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  }
  await page.setViewportSize({ width: 709, height: 844 });
  await page.locator('#manual-privacy').scrollIntoViewIfNeeded();
  expect(await header.evaluate(element => getComputedStyle(element).backgroundColor)).toBe('rgb(247, 251, 249)');
  await expect(header).not.toHaveAttribute('data-liquid-glass');
  await page.screenshot({ path: 'test-results/header-tablet-reading.png', animations: 'disabled' });
  await page.getByRole('button', { name: 'More navigation', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Overview', exact: true })).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(page.getByRole('button', { name: 'More navigation', exact: true })).toBeFocused();
});

test('desktop utility header fits its contents instead of stretching an empty white bar', async ({ page }) => {
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.goto('/?demo=1');
  const header = await page.locator('.topbar').boundingBox();
  const content = await page.locator('.main-shell').boundingBox();
  expect(header!.width).toBeLessThan(content!.width * 0.8);
  await expect(page.locator('.topbar .ph-clock')).toBeVisible();
  await page.screenshot({ path: 'test-results/header-desktop-compact.png', animations: 'disabled' });
});
