import { test, expect } from './fixtures';
import type { BrowserContext, Page } from '@playwright/test';

// Exercise the real Capacitor JS wrapper/client with a simulated OS bridge.
// This verifies credential lifecycle, not physical Android Keystore behavior.
async function nativeFixture(context: BrowserContext) {
  const vault = new Map<string, string>();
  const state = { unauthorized: false, unavailable: false, writeFails: false, logoutCalls: 0 };
  await context.exposeFunction('vaultCall', async (_plugin: string, method: string, options: { prefixedKey: string; data?: string }) => {
    if (method === 'internalGetItem') return { data: vault.get(options.prefixedKey) ?? null };
    if (method === 'internalSetItem') {
      if (state.writeFails) throw new Error('Simulated OS storage error');
      vault.set(options.prefixedKey, options.data!); return;
    }
    if (method === 'internalRemoveItem') return { success: vault.delete(options.prefixedKey) };
    throw new Error(`Unexpected simulated bridge call: ${method}`);
  });
  await context.addInitScript(() => {
    Object.assign(window, {
      CapacitorCustomPlatform: { name: 'android' },
      Capacitor: {
        PluginHeaders: [{ name: 'SecureStorage', methods: ['internalGetItem', 'internalSetItem', 'internalRemoveItem'].map(name => ({ name, rtype: 'promise' })) }],
        nativePromise: (plugin: string, method: string, options: unknown) => (window as unknown as { vaultCall: (plugin: string, method: string, options: unknown) => Promise<unknown> }).vaultCall(plugin, method, options),
      },
    });
  });
  const user = { id: 99999, name: 'Device Session Test', email: 'device@example.test', role: 'citizen' };
  await context.route('**/api/auth/me', route => route.fulfill({
    status: state.unavailable ? 503 : state.unauthorized || route.request().headers().authorization !== 'Bearer synthetic-device-credential' ? 401 : 200,
    json: state.unavailable || state.unauthorized || !route.request().headers().authorization ? { message: 'Session unavailable' } : user,
  }));
  await context.route('**/api/auth/login', route => route.fulfill({ json: { user, token: 'synthetic-device-credential' } }));
  await context.route('**/api/auth/logout', route => { state.logoutCalls++; return route.fulfill({ json: { message: 'Signed out.' } }); });
  await context.route('**/api/notifications', route => route.fulfill({ json: [] }));
  return { vault, state };
}

async function login(page: Page) {
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const dialog = page.getByRole('dialog', { name: 'Welcome to your community' });
  await dialog.getByLabel('Email address').fill('device@example.test');
  await dialog.getByLabel('Password', { exact: true }).fill('synthetic-device-password');
  await dialog.getByRole('button', { name: 'Sign in', exact: true }).click();
}

test('native session restores across WebView restarts, survives an outage and clears on revocation/logout', async ({ page, context }) => {
  const { vault, state } = await nativeFixture(context);
  await page.goto('/');
  await expect(page.locator('.connection')).toHaveText('Connected');
  await login(page);
  await expect(page.locator('.sidebar-user')).toContainText('Device Session Test');
  expect(vault.size).toBe(1);
  expect(await page.evaluate(() => JSON.stringify(localStorage))).not.toContain('synthetic-device-credential');
  await page.reload();
  await expect(page.locator('.sidebar-user')).toContainText('Device Session Test');
  state.unavailable = true;
  await page.reload();
  await expect(page.locator('.connection')).toHaveText('Connected');
  expect(vault.size).toBe(1);
  state.unavailable = false;
  await page.reload();
  await expect(page.locator('.sidebar-user')).toContainText('Device Session Test');
  state.unauthorized = true;
  await page.reload();
  await expect(page.locator('.connection')).toHaveText('Connected');
  expect(vault.size).toBe(0);
  await expect(page.locator('.sidebar-user')).toContainText('Community guest');
  state.unauthorized = false;
  await login(page);
  await expect(page.locator('.sidebar-user')).toContainText('Device Session Test');
  await page.getByRole('button', { name: 'Sign out', exact: true }).click();
  await page.getByRole('dialog', { name: 'Sign out?' }).getByRole('button', { name: 'Sign out', exact: true }).click();
  await expect(page.locator('.sidebar-user')).toContainText('Community guest');
  expect(vault.size).toBe(0);
  expect(state.logoutCalls).toBe(1);
});

test('native login fails clearly and revokes its new token if secure storage fails', async ({ page, context }) => {
  const { vault, state } = await nativeFixture(context);
  state.writeFails = true;
  await page.goto('/');
  await expect(page.locator('.connection')).toHaveText('Connected');
  await login(page);
  await expect(page.getByRole('alert')).toContainText('Unable to securely save your session');
  expect(vault.size).toBe(0);
  expect(state.logoutCalls).toBe(1);
  await expect(page.locator('.sidebar-user')).toContainText('Community guest');
});

test('a late unauthorized response from startup cannot erase a newly signed-in session', async ({ page, context }) => {
  const { vault } = await nativeFixture(context);
  let release: () => void = () => {};
  const wait = new Promise<void>(resolve => { release = resolve; });
  await page.route('**/api/auth/me', async route => {
    await wait;
    await route.fulfill({ status: 401, json: { message: 'Old anonymous request' } });
  });
  await page.goto('/');
  await login(page);
  await expect(page.locator('.sidebar-user')).toContainText('Device Session Test');
  const oldResponse = page.waitForResponse('**/api/auth/me');
  release();
  await oldResponse;
  await expect(page.locator('.connection')).toHaveText('Connected');
  await expect(page.locator('.sidebar-user')).toContainText('Device Session Test');
  expect(vault.size).toBe(1);
});
