import { test, expect } from './fixtures';
import type { Page } from '@playwright/test';

async function login(page: Page) {
  await page.goto('/');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const dialog = page.getByRole('dialog', { name: 'Welcome to your community' });
  await dialog.getByLabel('Email address').fill('citizen.one@example.test');
  await dialog.getByLabel('Password', { exact: true }).fill(process.env.E2E_CITIZEN_PASSWORD!);
  await dialog.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page.locator('.sidebar-user')).toContainText('Sample Citizen One');
}

test('remembered web login returns in a new browser context and logout removes it', async ({ page, context, browser, baseURL }) => {
  await login(page);
  const state = await context.storageState();
  const remember = state.cookies.filter(cookie => cookie.name.startsWith('remember_'));
  expect(remember).toHaveLength(1);
  expect(remember[0].httpOnly).toBe(true);
  expect(remember[0].expires).toBeGreaterThan(Date.now() / 1000 + 89 * 86400);
  const reopened = await browser.newContext({ baseURL, storageState: { cookies: state.cookies.filter(cookie => cookie.name === 'munialert_device'), origins: [] } });
  try {
    const returned = await reopened.newPage();
    await returned.goto('/');
    await expect(returned.locator('.sidebar-user')).toContainText('Sample Citizen One');
    await returned.getByRole('button', { name: 'Sign out', exact: true }).click();
    await returned.getByRole('dialog', { name: 'Sign out?' }).getByRole('button', { name: 'Sign out', exact: true }).click();
    await expect(returned.locator('.sidebar-user')).toContainText('Community guest');
    await returned.reload();
    await expect(returned.locator('.sidebar-user')).toContainText('Community guest');
    expect((await reopened.cookies()).filter(cookie => cookie.name.startsWith('remember_'))).toHaveLength(0);
  } finally { await reopened.close(); }
});

test('signing out this browser preserves another registered browser after restart', async ({ page, browser, baseURL }) => {
  const other = await browser.newContext({ baseURL });
  try {
    const second = await other.newPage(); await login(second); await login(page);
    const proof = (await other.cookies()).filter(cookie => cookie.name === 'munialert_device');
    await page.getByRole('button', { name: 'Sign out', exact: true }).click();
    await page.getByRole('dialog', { name: 'Sign out?' }).getByRole('button', { name: 'Sign out', exact: true }).click();
    await expect(page.locator('.sidebar-user')).toContainText('Community guest');
    await other.clearCookies(); await other.addCookies(proof); await second.reload();
    await expect(second.locator('.sidebar-user')).toContainText('Sample Citizen One');
  } finally { await other.close(); }
});

test('password change keeps this browser signed in and rejects another browser session', async ({ page, browser, baseURL }) => {
  const otherContext = await browser.newContext({ baseURL });
  try {
    const other = await otherContext.newPage();
    await login(other);
    await login(page);
    await page.getByRole('button', { name: 'My profile', exact: true }).click();
    await page.getByLabel('Current password', { exact: true }).fill(process.env.E2E_CITIZEN_PASSWORD!);
    await page.getByLabel('New password', { exact: true }).fill('changed-isolated-browser-password');
    await page.getByLabel('Confirm new password', { exact: true }).fill('changed-isolated-browser-password');
    await page.getByRole('button', { name: 'Save profile', exact: true }).click();
    await page.getByRole('dialog', { name: 'Save profile changes?' }).getByRole('button', { name: 'Save profile', exact: true }).click();
    await expect(page.getByText('Profile updated.', { exact: true })).toBeVisible();
    await page.reload();
    await expect(page.locator('.sidebar-user')).toContainText('Sample Citizen One');
    await other.reload();
    await expect(other.locator('.sidebar-user')).toContainText('Community guest');
  } finally { await otherContext.close(); }
});
