import { test, expect, openReading, navigateWorkspace, continueReport } from './fixtures';
import type { Locator } from '@playwright/test';

async function visibleNumber(number: Locator) {
  return number.locator('.number-reels').evaluate(element => [...element.children].map(part => {
    if (!part.classList.contains('number-reel')) return part.textContent;
    const bounds = part.getBoundingClientRect(); const middle = bounds.top + bounds.height / 2;
    return [...part.querySelectorAll('.number-reel-digit')].find(row => { const rect = row.getBoundingClientRect(); return rect.top <= middle && rect.bottom > middle; })?.textContent;
  }).join(''));
}

test('reading controls reflow at 200%, persist across restart, and reset', async ({ page }) => {
  const errors: string[] = []; page.on('pageerror', error => errors.push(error.message));
  await page.setViewportSize({ width: 375, height: 812 });
  await page.goto('/?demo=1');
  await openReading(page);
  const panel = page.getByRole('dialog', { name: 'Reading & accessibility', exact: true });
  await page.screenshot({ path: 'test-results/reading-settings-phone.png', animations: 'disabled' });
  await panel.getByRole('slider', { name: 'Text size', exact: true }).fill('200');
  await panel.getByRole('switch', { name: 'Bold text', exact: true }).check();
  await panel.getByRole('switch', { name: 'High contrast', exact: true }).check();
  await panel.getByRole('switch', { name: 'More reading space', exact: true }).check();
  await panel.getByRole('switch', { name: 'Reduce motion', exact: true }).check();
  await panel.getByRole('switch', { name: 'Keep messages visible', exact: true }).check();
  await expect(page.locator('html')).toHaveClass(/reduceMotion/);
  expect(await panel.locator('.reading-preview p').evaluate(el => parseFloat(getComputedStyle(el).fontSize))).toBe(32);
  await expect.poll(() => panel.evaluate(el => el.scrollWidth <= el.clientWidth + 1)).toBe(true);
  await page.screenshot({ path: 'test-results/reading-settings-200-phone.png' });
  await panel.getByRole('button', { name: 'Close dialog', exact: true }).click();
  for (const width of [320, 375, 768, 1920]) {
    await page.setViewportSize({ width, height: 900 });
    await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    expect(await page.locator('.page-heading p').evaluate(el => parseFloat(getComputedStyle(el).fontSize))).toBe(32);
  }
  await page.setViewportSize({ width: 375, height: 812 });
  await page.reload();
  await expect(page.locator('html')).toHaveAttribute('data-text-size', '200');
  await expect(page.locator('html')).toHaveClass(/highContrast/);
  await page.getByRole('button', { name: 'Create hazard report', exact: true }).click(); await continueReport(page);
  const report = page.getByRole('dialog', { name: 'Report a hazard', exact: true });
  await expect.poll(() => report.evaluate(el => el.scrollWidth <= el.clientWidth + 1)).toBe(true);
  await report.getByLabel('Report title', { exact: true }).fill('Readable at large text size');
  await page.screenshot({ path: 'test-results/report-large-text-phone.png' });
  await report.getByRole('button', { name: 'Close dialog', exact: true }).click();
  await page.getByRole('dialog', { name: 'Discard this report?' }).getByRole('button', { name: 'Discard report', exact: true }).click();
  await openReading(page);
  await panel.getByRole('button', { name: 'Reset reading settings', exact: true }).click();
  await expect(panel.getByRole('slider', { name: 'Text size', exact: true })).toHaveValue('100');
  await expect(panel.getByRole('switch', { name: 'High contrast', exact: true })).not.toBeChecked();
  await expect(panel.getByRole('switch', { name: 'Keep messages visible', exact: true })).not.toBeChecked();
  expect(errors).toEqual([]);
});

test('reading controls remain usable with denied local storage and device reduced motion', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.addInitScript(() => { Storage.prototype.getItem = () => { throw new DOMException('Unavailable'); }; Storage.prototype.setItem = () => { throw new DOMException('Unavailable'); }; });
  await page.goto('/?demo=1');
  await openReading(page);
  const panel = page.getByRole('dialog', { name: 'Reading & accessibility', exact: true });
  await panel.getByRole('slider', { name: 'Text size', exact: true }).fill('130');
  await expect(page.locator('html')).toHaveAttribute('data-text-size', '130');
  await panel.getByRole('button', { name: 'Reset reading settings', exact: true }).click();
  await panel.getByRole('button', { name: 'Close dialog', exact: true }).click();
  await expect(page.locator('html')).toHaveClass(/reduceMotion/);
  await navigateWorkspace(page, 'Dashboard');
  await expect(page.locator('.metric-value .animated-number').first().locator('.visually-hidden')).toHaveText('06');
  expect(await visibleNumber(page.locator('.metric-value .animated-number').first())).toBe('06');
});

test('errors stay in the active dialog, keep input focus and can be dismissed', async ({ page }) => {
  await page.goto('/');
  await page.route('**/auth/login', route => route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ message: 'The email or password is incorrect. Please try again.' }) }));
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community', exact: true });
  await auth.getByLabel('Email address', { exact: true }).fill('unknown@example.test');
  await auth.getByLabel('Password', { exact: true }).fill('incorrect-test-password');
  await auth.getByRole('button', { name: 'Sign in', exact: true }).click();
  const toast = auth.locator('.toast-message.error');
  await expect(toast).toContainText('The email or password is incorrect');
  await expect(toast).toHaveCount(1);
  await expect(auth.locator('.form-error')).toHaveCount(0);
  await expect(auth.getByText('The email or password is incorrect. Please try again.', { exact: true })).toHaveCount(1);
  await expect(auth.getByLabel('Email address', { exact: true })).toBeFocused();
  await auth.getByLabel('Password', { exact: true }).scrollIntoViewIfNeeded();
  await auth.screenshot({ path: 'test-results/login-single-toast.png', animations: 'disabled' });
  await page.clock.install(); await page.clock.fastForward(20000);
  await expect(toast).toBeVisible();
  await toast.getByRole('button', { name: 'Dismiss notification', exact: true }).click();
  await expect(toast).toHaveCount(0);
  await auth.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(toast).toBeVisible();
});

test('export and refresh report outcomes, hover pauses dismissal and messages are distinct', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/?demo=1');
  await page.getByRole('button', { name: 'Refresh reports', exact: true }).click();
  const refreshed = page.locator('.toast-message.success').filter({ hasText: 'Demo reports refreshed.' });
  await expect(refreshed).toBeVisible();
  await page.clock.install();
  await refreshed.hover(); await page.clock.fastForward(10000);
  await expect(refreshed).toBeVisible();
  await page.mouse.move(0, 0); await page.clock.fastForward(9000);
  await expect(refreshed).toHaveCount(0);
  const download = page.waitForEvent('download');
  await page.getByRole('button', { name: 'Export page', exact: true }).click();
  expect((await download).suggestedFilename()).toContain('demo');
  await expect(page.locator('.toast-message.success')).toContainText('Report page prepared for download.');
});

test('dashboard digit reels spin, settle on exact grouped counts and stop for reduced motion', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await page.addInitScript(() => {
    (window as any).numberRolls = 0;
    const original = Element.prototype.animate;
    Element.prototype.animate = function(...args: Parameters<Element['animate']>) {
      if (this.classList.contains('number-reel-track')) (window as any).numberRolls++;
      return original.apply(this, args);
    };
  });
  let count = 100;
  await page.route('**/api/stats', route => route.fulfill({ json: { total: count, active: count, pending: 20, responding: 10, resolved: 25 } }));
  await page.goto('/');
  const metric = page.locator('.metric-value .animated-number').first();
  await expect(metric.locator('.visually-hidden')).toHaveText('100');
  await expect.poll(() => page.evaluate(() => (window as any).numberRolls)).toBeGreaterThanOrEqual(3);
  await expect.poll(() => visibleNumber(metric)).toBe('100');
  count = 10002;
  await page.getByRole('button', { name: 'Refresh reports', exact: true }).click();
  await expect(metric.locator('.visually-hidden')).toHaveText('10,002');
  await expect.poll(() => visibleNumber(metric)).toBe('10,002');
  expect(await metric.evaluate(el => el.scrollWidth <= el.clientWidth + 1)).toBe(true);
  await page.emulateMedia({ reducedMotion: 'reduce' });
  count = 7;
  await page.getByRole('button', { name: 'Refresh reports', exact: true }).click();
  await expect(metric.locator('.visually-hidden')).toHaveText('07');
  expect(await visibleNumber(metric)).toBe('07');
  expect(await metric.evaluate(el => el.getAnimations({ subtree: true }).length)).toBe(0);
  count = 0;
  await page.getByRole('button', { name: 'Refresh reports', exact: true }).click();
  await expect(metric.locator('.visually-hidden')).toHaveText('00');
  expect(await visibleNumber(metric)).toBe('00');
});

test('text-size slider supports dragging keyboard steps boundaries and intermediate-size persistence', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 812 });
  await page.goto('/?demo=1');
  await openReading(page);
  const panel = page.getByRole('dialog', { name: 'Reading & accessibility' });
  const slider = panel.getByRole('slider', { name: 'Text size', exact: true });
  await expect(slider).toHaveValue('100');
  await expect(panel.getByRole('button', { name: 'Decrease text size', exact: true })).toBeDisabled();
  const track = (await slider.boundingBox())!;
  await page.mouse.click(track.x + track.width * .65, track.y + track.height / 2);
  await expect.poll(async () => Number(await slider.inputValue())).toBeGreaterThan(100);
  await expect(slider).toBeFocused();
  await slider.press('Home'); await slider.press('ArrowRight');
  await expect(slider).toHaveValue('105');
  await expect(page.locator('html')).toHaveAttribute('data-text-size', '105');
  await panel.getByRole('button', { name: 'Increase text size', exact: true }).click();
  await expect(slider).toHaveValue('110');
  await slider.fill('165');
  await expect(slider).toHaveAttribute('aria-valuetext', '165% text size');
  await expect(page.locator('html')).toHaveClass(/largeText/);
  for (const width of [320, 375, 844]) {
    await page.setViewportSize({ width, height: 812 });
    expect(await panel.evaluate(el => el.scrollWidth <= el.clientWidth + 1)).toBe(true);
    expect(await panel.locator('.reading-size-summary output').evaluate(el => { const range = document.createRange(); range.selectNodeContents(el); return range.getClientRects().length; })).toBe(1);
  }
  await page.setViewportSize({ width: 375, height: 812 });
  await page.screenshot({ path: 'test-results/reading-slider-165-phone.png', animations: 'disabled' });
  await page.reload();
  await expect(page.locator('html')).toHaveAttribute('data-text-size', '165');
  await openReading(page);
  await expect(slider).toHaveValue('165');
  await slider.press('End');
  await expect(slider).toHaveValue('200');
  await expect(panel.getByRole('button', { name: 'Increase text size', exact: true })).toBeDisabled();
  await panel.getByRole('button', { name: 'Reset reading settings', exact: true }).click();
  await expect(slider).toHaveValue('100');
});

test('rolling counters keep the newest value during rapid background updates and cancel on request', async ({ page }) => {
  await page.clock.install();
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  let count = 99;
  await page.route('**/api/stats', route => route.fulfill({ json: { total: count, active: count, pending: 0, responding: 0, resolved: 0 } }));
  await page.goto('/');
  const metric = page.locator('.metric-value .animated-number').first();
  await expect(metric.locator('.visually-hidden')).toHaveText('99');
  await metric.evaluate(el => (window as any).originalCounter = el);
  count = 10002;
  await page.clock.fastForward(60000);
  await expect(metric.locator('.visually-hidden')).toHaveText('10,002');
  expect(await metric.evaluate(el => el === (window as any).originalCounter)).toBe(true);
  count = 7;
  await page.clock.fastForward(60000);
  await expect(metric.locator('.visually-hidden')).toHaveText('07');
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await expect(page.locator('html')).toHaveClass(/reduceMotion/);
  expect(await visibleNumber(metric)).toBe('07');
  expect(await metric.evaluate(el => el.getAnimations({ subtree: true }).length)).toBe(0);
  await page.getByRole('button', { name: 'Verified news', exact: true }).click();
  await expect(metric).toHaveCount(0);
});

test('large-text manual, navigation and landscape forms keep controls reachable', async ({ page }) => {
  await page.addInitScript(() => localStorage.setItem('munialert-reading-v1', JSON.stringify({ textSize: 200, reduceMotion: true })));
  await page.setViewportSize({ width: 375, height: 812 });
  await page.goto('/?demo=1&view=guide');
  await expect(page.locator('.guide-hero')).toBeVisible();
  for (const font of ['platform', 'Arial, sans-serif']) {
    const override = font === 'platform' ? null : await page.addStyleTag({ content: `:root { --font-system: ${font}; }` });
    await test.step(`Guide reflows with ${font} fonts at 200%`, async () => {
      for (const width of [320, 375]) {
        await page.setViewportSize({ width, height: 812 });
        await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
      }
    });
    await override?.evaluate(element => element.remove());
  }
  await openReading(page);
  const panel = page.getByRole('dialog', { name: 'Reading & accessibility', exact: true });
  await expect(panel).toBeVisible();
  await panel.getByRole('button', { name: 'Close dialog', exact: true }).click();
  await page.getByRole('button', { name: 'Create hazard report', exact: true }).click(); await continueReport(page);
  await page.setViewportSize({ width: 844, height: 390 });
  const report = page.getByRole('dialog', { name: 'Report a hazard', exact: true });
  await expect(report.getByRole('button', { name: 'Submit hazard report', exact: true })).toBeVisible();
  expect(await report.locator('.modal-body').evaluate(el => el.clientHeight)).toBeGreaterThan(60);
  await expect.poll(() => report.evaluate(el => el.scrollWidth <= el.clientWidth + 1)).toBe(true);
});
