import { test, expect, navigateWorkspace, openReading, fillTwoFactor, adminOtp } from './fixtures';
import type { Page } from '@playwright/test';
const password = 'synthetic-account-settings-password';
async function login(page: Page, email: string, secret = password) {
  await page.goto('/'); await page.getByRole('button', { name: 'Account', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community', exact: true });
  await auth.getByLabel('Email address', { exact: true }).fill(email);
  await auth.getByLabel('Password', { exact: true }).fill(secret);
  await auth.getByRole('button', { name: 'Sign in', exact: true }).click();
}

test('Settings owns reading and guide controls and restores each account separately across devices', async ({ page, browser, request, baseURL }) => {
  const origin = `http://127.0.0.1:${process.env.HAZARD_API_PORT || '18000'}`;
  const accounts = [];
  for (const name of ['SettingsAlpha', 'SettingsBeta']) {
    const email = `${name}-${Date.now()}@example.test`;
    const response = await request.post(`${origin}/api/auth/register`, { data: { name, email, password } });
    expect(response.status()).toBe(201); accounts.push({ name, email, token: (await response.json()).token });
  }
  const [alpha, beta] = accounts;
  await login(page, alpha!.email);
  await expect(page.locator('.sidebar-user')).toContainText(alpha!.name);
  await expect(page.locator('.community-card')).toHaveCount(0);
  await expect(page.locator('.topbar').getByRole('button', { name: 'Reading and accessibility settings', exact: true })).toHaveCount(0);
  await expect(page.locator('.topbar').getByRole('button', { name: 'Open guide and user manual', exact: true })).toHaveCount(0);
  await expect(page.locator('.sidebar nav').getByRole('button', { name: 'Guide & user manual', exact: true })).toHaveCount(0);
  await openReading(page);
  await page.getByRole('slider', { name: 'Text size', exact: true }).fill('165');
  await page.getByRole('switch', { name: 'Bold text', exact: true }).check();
  await page.getByRole('button', { name: 'Close dialog', exact: true }).click();
  await expect.poll(async () => (await (await request.get(`${origin}/api/auth/me`, { headers: { Authorization: `Bearer ${alpha!.token}` } })).json()).reading_preferences?.textSize).toBe(165);
  await page.reload(); await expect(page.locator('html')).toHaveAttribute('data-text-size', '165');
  await navigateWorkspace(page, 'Settings');
  await page.getByRole('button', { name: 'Open guide and user manual', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Guide & user manual', exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Back to settings', exact: true }).click();
  await page.getByRole('button', { name: 'Sign out', exact: true }).click();
  await page.getByRole('dialog', { name: 'Sign out?', exact: true }).getByRole('button', { name: 'Sign out', exact: true }).click();
  await expect(page.locator('html')).toHaveAttribute('data-text-size', '100');
  await login(page, beta!.email);
  await expect(page.locator('.sidebar-user')).toContainText(beta!.name);
  await expect(page.locator('html')).toHaveAttribute('data-text-size', '100');
  await expect(page.locator('html')).not.toHaveClass(/boldText/);
  await openReading(page); await page.getByRole('slider', { name: 'Text size', exact: true }).fill('115');
  await page.getByRole('button', { name: 'Close dialog', exact: true }).click();
  await expect.poll(async () => (await (await request.get(`${origin}/api/auth/me`, { headers: { Authorization: `Bearer ${beta!.token}` } })).json()).reading_preferences?.textSize).toBe(115);
  const fresh = await browser.newContext({ baseURL });
  try {
    const device = await fresh.newPage(); await login(device, alpha!.email);
    await expect(device.locator('.sidebar-user')).toContainText(alpha!.name);
    await expect(device.locator('html')).toHaveAttribute('data-text-size', '165');
    await expect(device.locator('html')).toHaveClass(/boldText/);
    await navigateWorkspace(device, 'Settings');
    // Opt-out remains available if delivery later becomes unavailable or email
    // verification is lost. It must not depend on the enablement prerequisites.
    const optIn = await request.put(`${origin}/api/preferences/email`, { headers: { Authorization: `Bearer ${alpha!.token}` }, data: { account_id: (await (await request.get(`${origin}/api/auth/me`, { headers: { Authorization: `Bearer ${alpha!.token}` } })).json()).id, email_report_updates: true } });
    expect(optIn.ok()).toBe(true);
    await device.reload(); await navigateWorkspace(device, 'Settings');
    const updates = device.getByRole('switch', { name: 'Report update emails', exact: true });
    await expect(updates).toBeChecked(); await expect(updates).toBeEnabled();
    await updates.uncheck();
    await expect.poll(async () => (await (await request.get(`${origin}/api/auth/me`, { headers: { Authorization: `Bearer ${alpha!.token}` } })).json()).email_report_updates).toBe(false);
    await expect(device.locator('.toast-message.success').filter({ hasText: 'Report update emails turned off.' })).toBeVisible();
    const messages = device.getByRole('button', { name: 'Dismiss notification', exact: true });
    await messages.first().click();
    await expect(messages).toHaveCount(0);
    await device.screenshot({ path: 'test-results/account-settings-saved.png', animations: 'disabled' });
  } finally { await fresh.close(); }
});

test('PDEA-style 2FA accepts a pasted six-digit code with keyboard access and phone reflow', async ({ page }) => {
  await page.addInitScript(() => localStorage.setItem('munialert-reading-v1', JSON.stringify({ textSize: 200 })));
  await page.setViewportSize({ width: 375, height: 900 });
  await login(page, 'admin@example.test', process.env.E2E_ADMIN_PASSWORD!);
  const auth = page.getByRole('dialog', { name: 'Welcome to your community', exact: true });
  await expect(auth.getByRole('heading', { name: 'Verify your sign-in with 2FA', exact: true })).toBeVisible();
  const fields = auth.locator('.two-factor-digit');
  await expect(fields).toHaveCount(6);
  const denied = await page.request.get('/api/admin/reports'); expect(denied.status()).toBe(401);
  await fillTwoFactor(page, adminOtp());
  await fields.nth(4).focus(); await fields.nth(4).press('ArrowRight'); await expect(fields.nth(5)).toBeFocused();
  for (const field of await fields.all()) expect((await field.boundingBox())!.width).toBeGreaterThanOrEqual(48);
  await expect.poll(() => auth.evaluate(element => element.scrollWidth <= element.clientWidth + 1)).toBe(true);
  // Mask the synthetic entered code in the exported setup screenshot.
  for (const field of await fields.all()) await field.fill('');
  await auth.screenshot({ path: 'test-results/two-factor-phone-200.png', animations: 'disabled' });
  await fillTwoFactor(page, adminOtp());
  await auth.getByRole('button', { name: 'Verify and sign in', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Municipal dashboard', exact: true })).toBeVisible();
});
