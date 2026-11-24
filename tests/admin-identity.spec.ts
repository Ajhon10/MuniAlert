import { test, expect, finishAdminMfa, accountOption } from './fixtures';

test('administrator can withdraw optional ID and retain administration across reload without self-approval', async ({ page }) => {
  await page.goto('/'); await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community', exact: true });
  await auth.getByLabel('Email address').fill('admin@munialert.test');
  await auth.getByLabel('Password', { exact: true }).fill(process.env.E2E_ADMIN_PASSWORD!);
  await auth.getByRole('button', { name: 'Sign in', exact: true }).click(); await finishAdminMfa(page);
  await accountOption(page, 'Settings');
  const own = page.getByRole('region', { name: 'Administrator verification', exact: true });
  await expect(own).toContainText('Your administrator account is ready.');
  await own.getByRole('button', { name: 'Withdraw identity verification', exact: true }).click();
  await expect(own).toContainText('Withdrawn');
  await expect(page.locator('.identity-review')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Review reports', exact: true })).toBeVisible();
  const denied = await page.evaluate(async () => { const response = await fetch('/api/admin/stats', { headers: { Accept: 'application/json' } }); return { status: response.status, data: await response.json() }; });
  expect(denied.status).toBe(200); expect(denied.data.account_verification_required).toBeUndefined();
  const bytes = await page.evaluate(() => { const canvas = document.createElement('canvas'); canvas.width = 400; canvas.height = 200; return canvas.toDataURL('image/png').split(',')[1]!; });
  await own.getByLabel('One redacted government-ID image').setInputFiles({ name: 'synthetic-admin.png', mimeType: 'image/png', buffer: Buffer.from(bytes, 'base64') });
  await own.getByRole('checkbox', { name: /I consent to the operator processing this ID image/ }).check();
  await own.getByRole('button', { name: 'Submit ID for private review', exact: true }).click();
  await expect(own).toContainText('Awaiting administrator review');
  await expect(own).toContainText('You cannot approve your own ID.');
  await page.setViewportSize({ width: 375, height: 900 });
  await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await page.screenshot({ path: 'test-results/admin-identity-optional-phone.png', fullPage: true });
  await page.reload(); await expect(page.getByRole('region', { name: 'Administrator verification', exact: true })).toContainText('Awaiting administrator review');
  await expect(page.locator('.identity-review')).toBeVisible();
});
