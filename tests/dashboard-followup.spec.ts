import { test, expect, finishAdminMfa, openReading, navigateWorkspace } from './fixtures';
import type { Page } from '@playwright/test';

async function adminLogin(page: Page) {
  await page.goto('/'); await page.getByRole('button', { name: 'Account', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community' });
  await auth.getByLabel('Email address', { exact: true }).fill('admin@example.test');
  await auth.getByLabel('Password', { exact: true }).fill(process.env.E2E_ADMIN_PASSWORD!);
  await auth.getByRole('button', { name: 'Sign in', exact: true }).click(); await finishAdminMfa(page);
  await expect(page.locator('.sidebar-user')).toContainText('Municipal administrator');
}

test('administrator reopens with only its persistent device cookie and logout prevents replay', async ({ page, context, browser, baseURL }) => {
  await adminLogin(page);
  const device = (await context.cookies()).filter(cookie => cookie.name === 'munialert_device');
  expect(device).toHaveLength(1); expect(device[0]!.httpOnly).toBe(true);
  expect(device[0]!.expires).toBeGreaterThan(Date.now() / 1000 + 300 * 86400);
  const reopened = await browser.newContext({ baseURL, storageState: { cookies: device, origins: [] } });
  try {
    const returned = await reopened.newPage(); await returned.goto('/');
    await expect(returned.locator('.sidebar-user')).toContainText('Municipal administrator');
    expect(await returned.evaluate(async () => (await fetch('/api/admin/reports', { headers: { Accept: 'application/json' } })).status)).toBe(200);
    expect(await returned.evaluate(async () => {
      const csrf = document.cookie.split('; ').find(item => item.startsWith('XSRF-TOKEN='))!.slice(11);
      return (await fetch('/api/admin/dashboard-background', { method: 'POST', headers: { Accept: 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(csrf) } })).status;
    })).toBe(428);
    await returned.getByRole('button', { name: 'Sign out', exact: true }).click();
    await returned.getByRole('dialog', { name: 'Sign out?' }).getByRole('button', { name: 'Sign out', exact: true }).click();
    await expect(returned.locator('.sidebar-user')).toContainText('Community guest');
    await reopened.clearCookies(); await reopened.addCookies(device);
    await returned.reload();
    await expect(returned.locator('.sidebar-user')).toContainText('Community guest');
    expect(await returned.evaluate(async () => (await fetch('/api/admin/reports', { headers: { Accept: 'application/json' } })).status)).toBe(401);
  } finally { await reopened.close(); }
});

test('admin previews publishes and removes the shared dashboard image', async ({ page }) => {
  await adminLogin(page); await page.getByRole('button', { name: 'Content management', exact: true }).click();
  const image = await page.evaluate(() => { const canvas = document.createElement('canvas'); canvas.width = 960; canvas.height = 540; const ctx = canvas.getContext('2d')!; ctx.fillStyle = '#175b46'; ctx.fillRect(0, 0, 960, 540); ctx.fillStyle = '#dbba83'; ctx.fillRect(480, 0, 480, 540); return canvas.toDataURL('image/png').split(',')[1]!; });
  await page.getByLabel('Choose background image', { exact: true }).setInputFiles({ name: 'approved-community.png', mimeType: 'image/png', buffer: Buffer.from(image, 'base64') });
  await expect(page.getByAltText('Dashboard background preview')).toHaveAttribute('src', /^blob:/);
  await page.getByRole('button', { name: 'Publish dashboard background', exact: true }).click();
  await page.getByRole('dialog', { name: 'Publish dashboard background?' }).getByRole('button', { name: 'Publish background', exact: true }).click();
  await expect(page.getByAltText('Dashboard background preview')).toHaveAttribute('src', /\/api\/dashboard-background\//);
  await page.getByRole('button', { name: 'Dashboard', exact: true }).click();
  await expect(page.locator('.dashboard-backdrop')).toBeVisible();
  await page.reload(); await expect(page.locator('.dashboard-backdrop')).toBeVisible();
  await page.screenshot({ path: 'test-results/dashboard-custom-background.png', fullPage: true, animations: 'disabled' });
  await page.getByRole('button', { name: 'Content management', exact: true }).click();
  await page.getByRole('button', { name: 'Remove background', exact: true }).click();
  await page.getByRole('dialog', { name: 'Remove dashboard background?' }).getByRole('button', { name: 'Remove background', exact: true }).click();
  await expect(page.getByAltText('Dashboard background preview')).toHaveCount(0);
});

test('reading controls live in Settings and reflow at 200 percent', async ({ page }) => {
  await page.addInitScript(() => localStorage.setItem('munialert-reading-v1', JSON.stringify({ textSize: 200 })));
  await page.setViewportSize({ width: 375, height: 812 }); await page.goto('/?demo=1');
  await expect(page.locator('.reading-link')).toHaveCount(0);
  await expect(page.locator('.community-card')).toHaveCount(0);
  await expect(page.locator('.topbar').getByRole('button', { name: 'Reading and accessibility settings', exact: true })).toHaveCount(0);
  await openReading(page);
  const panel = page.getByRole('dialog', { name: 'Reading & accessibility', exact: true });
  await expect(panel).toBeVisible();
  expect(await panel.evaluate(el => el.scrollWidth <= el.clientWidth + 1)).toBe(true);
  await page.screenshot({ path: 'test-results/settings-reading-200.png', animations: 'disabled' });
});

test('navigation plays entry motion and obeys system and app reduced motion', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await page.addInitScript(() => { const original = Element.prototype.animate; (window as any).__entryMotion = 0; Element.prototype.animate = function(...args: Parameters<Element['animate']>) { if (this.id === 'main-content') (window as any).__entryMotion++; return original.apply(this, args); }; });
  await page.goto('/?demo=1');
  await expect.poll(() => page.evaluate(() => (window as any).__entryMotion)).toBeGreaterThan(0);
  const first = await page.evaluate(() => (window as any).__entryMotion);
  await page.getByRole('button', { name: 'Verified news', exact: true }).click();
  await expect.poll(() => page.evaluate(() => (window as any).__entryMotion)).toBeGreaterThan(first);
  await page.emulateMedia({ reducedMotion: 'reduce' });
  const reduced = await page.evaluate(() => (window as any).__entryMotion);
  await page.getByRole('button', { name: 'Dashboard', exact: true }).click();
  expect(await page.evaluate(() => (window as any).__entryMotion)).toBe(reduced);
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await openReading(page);
  await page.getByRole('switch', { name: 'Reduce motion', exact: true }).check();
  await page.getByRole('button', { name: 'Close dialog', exact: true }).click();
  // Entering Settings played motion before the preference was enabled.
  const appReduced = await page.evaluate(() => (window as any).__entryMotion);
  await page.getByRole('button', { name: 'Verified news', exact: true }).click();
  expect(await page.evaluate(() => (window as any).__entryMotion)).toBe(appReduced);
  await page.getByRole('button', { name: 'Dashboard', exact: true }).click();
  expect(await page.evaluate(() => (window as any).__entryMotion)).toBe(appReduced);
});
