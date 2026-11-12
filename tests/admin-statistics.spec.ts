import { test, expect, finishAdminMfa, navigateWorkspace, accountOption } from './fixtures';
import type { Page } from '@playwright/test';
import { readFile } from 'node:fs/promises';

async function login(page: Page) {
  await page.goto('/'); await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const form = page.getByRole('dialog', { name: 'Welcome to your community', exact: true });
  await form.getByLabel('Email address').fill('admin@example.test');
  await form.getByLabel('Password', { exact: true }).fill(process.env.E2E_ADMIN_PASSWORD!);
  await form.getByRole('button', { name: 'Sign in', exact: true }).click(); await finishAdminMfa(page);
}

test('admin dashboard and summary display API category counts with day month year filters and genuine document downloads', async ({ page, request }) => {
  const origin = `http://127.0.0.1:${process.env.HAZARD_API_PORT || '18000'}`;
  const session = await request.post(`${origin}/api/auth/login`, { data: { email: 'citizen.one@example.test', password: process.env.E2E_CITIZEN_PASSWORD } });
  expect(session.ok()).toBe(true); const token = (await session.json()).token;
  await page.goto('/');
  const photo = await page.evaluate(() => { const canvas = document.createElement('canvas'); canvas.width = canvas.height = 30; canvas.getContext('2d')!.fillRect(0, 0, 30, 30); return canvas.toDataURL('image/png').split(',')[1]!; });
  for (const category of ['fire', 'tree']) {
    const created = await request.post(`${origin}/api/reports`, { headers: { Authorization: `Bearer ${token}` }, multipart: { category_id: category, title: `PRIVATE statistics ${category}`, description: 'Synthetic private eyewitness evidence.', address: 'PRIVATE address', barangay: 'Anambongan', severity: 'moderate', selected_latitude: '15.864', selected_longitude: '120.399', location_source: 'manual', location_confirmed: '1', 'photos[0]': { name: 'synthetic.png', mimeType: 'image/png', buffer: Buffer.from(photo, 'base64') } } });
    expect(created.status()).toBe(201);
  }
  await login(page);
  const analytics = page.locator('.incident-analytics');
  await expect(analytics.getByRole('img', { name: /month report counts/ })).toBeVisible();
  await expect(page.locator('.sidebar')).not.toContainText('Settings');
  await expect(page.locator('.sidebar')).not.toContainText('My profile');
  for (const group of ['day', 'month', 'year']) {
    await analytics.getByRole('combobox', { name: 'Group records', exact: true }).selectOption(group);
    const updated = page.waitForResponse(response => new URL(response.url()).pathname === '/api/admin/analytics' && new URL(response.url()).searchParams.get('group') === group);
    await analytics.getByRole('button', { name: 'Update summary', exact: true }).click();
    const data = await (await updated).json(); expect(data.total).toBeGreaterThanOrEqual(2);
    await expect(analytics.getByRole('img', { name: `${group} report counts by hazard category`, exact: true })).toBeVisible();
    await expect(analytics.locator('.analytics-total .visually-hidden')).toHaveText(data.total.toLocaleString());
    const categories = analytics.locator('.category-stat');
    for (const category of data.categories) {
      await expect(categories.filter({ hasText: category.name }).locator('strong')).toHaveText(category.total.toLocaleString());
    }
  }
  await analytics.locator('.statistics-table summary').click();
  await expect(analytics.locator('.data-table tbody tr')).not.toHaveCount(0);
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.screenshot({ path: 'test-results/admin-dashboard-yearly-statistics.png', fullPage: true });
  await navigateWorkspace(page, 'Incident summary');
  await expect(analytics.getByRole('img', { name: /month report counts/ })).toBeVisible();
  await analytics.getByRole('combobox', { name: 'Incident category', exact: true }).selectOption('fire');
  await analytics.getByRole('button', { name: 'Update summary', exact: true }).click();
  await expect(analytics.locator('.category-stat')).toHaveCount(1);
  // Editing an unapplied control must not silently change the exported chart filters.
  await analytics.getByRole('combobox', { name: 'Group records', exact: true }).selectOption('day');
  for (const format of ['pdf', 'docx', 'xlsx']) {
    await analytics.getByRole('combobox', { name: 'Export format', exact: true }).selectOption(format);
    const requested = page.waitForRequest(request => new URL(request.url()).pathname === `/api/admin/analytics/export/${format}`);
    const download = page.waitForEvent('download'); await analytics.getByRole('button', { name: 'Export summary', exact: true }).click();
    const url = new URL((await requested).url()); expect(url.searchParams.get('group')).toBe('month'); expect(url.searchParams.get('category')).toBe('fire');
    const file = await download; expect(file.suggestedFilename()).toMatch(new RegExp(`^munialert-statistics-month-.*\\.${format}$`));
    const bytes = await readFile((await file.path())!); expect(bytes.subarray(0, format === 'pdf' ? 5 : 2).toString()).toBe(format === 'pdf' ? '%PDF-' : 'PK');
    await expect(analytics.getByRole('button', { name: 'Export summary', exact: true })).toBeEnabled();
  }
  await page.setViewportSize({ width: 375, height: 850 });
  await page.addStyleTag({ content: 'html { font-size: 200% !important; }' });
  await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
  await analytics.getByRole('button', { name: 'Last 5 years', exact: true }).click();
  await expect(analytics.getByRole('img', { name: /year report counts/ })).toBeVisible();
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.screenshot({ path: 'test-results/admin-summary-phone-200.png', fullPage: true });
});

test('header account menu supports keyboard profile settings and logout while guests keep header sign-in and reading settings', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('.topbar').getByRole('button', { name: 'Sign in', exact: true })).toBeVisible();
  await expect(page.locator('.sidebar').getByRole('button', { name: 'Sign in', exact: true })).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Open settings', exact: true })).toHaveCount(0);
  await accountOption(page, 'Settings'); await expect(page.getByRole('button', { name: 'Reading and accessibility settings', exact: true })).toBeVisible();
  await login(page);
  const trigger = page.getByRole('button', { name: 'Account', exact: true });
  await trigger.focus(); await trigger.press('ArrowDown');
  const menu = page.getByRole('menu', { name: 'Account options', exact: true });
  await expect(menu.getByRole('menuitem', { name: 'My profile', exact: true })).toBeFocused();
  await page.keyboard.press('ArrowDown'); await expect(menu.getByRole('menuitem', { name: 'Settings', exact: true })).toBeFocused();
  await page.keyboard.press('Escape'); await expect(menu).toHaveCount(0); await expect(trigger).toBeFocused();
  await accountOption(page, 'My profile'); await expect(page.getByRole('heading', { name: 'My profile & badges', exact: true })).toBeVisible();
  await accountOption(page, 'Settings'); await expect(page.getByRole('heading', { name: 'Settings', exact: true })).toBeVisible();
  await accountOption(page, 'Log out');
  await page.getByRole('dialog', { name: 'Sign out?', exact: true }).getByRole('button', { name: 'Sign out', exact: true }).click();
  await expect(page.locator('.topbar').getByRole('button', { name: 'Sign in', exact: true })).toBeVisible();
});
