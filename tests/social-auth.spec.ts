import { test, expect } from './fixtures';

for (const width of [1280, 375]) test(`only email/password sign-in is offered at ${width}px and old provider returns cannot sign in`, async ({ page }) => {
  await page.setViewportSize({ width, height: 900 });
  const providerRequests: string[] = [];
  page.on('request', request => { if (/\/(?:api\/auth\/(?:providers|social)|auth\/social)/.test(new URL(request.url()).pathname)) providerRequests.push(request.url()); });
  await page.goto('/?social_auth=success&social_intent=profile&provider=google');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community', exact: true });
  await expect(auth.getByLabel('Email address')).toBeVisible();
  await expect(auth.getByLabel('Password', { exact: true })).toBeVisible();
  await expect(auth.getByRole('button', { name: /Google|Facebook|Connect provider/i })).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Account', exact: true })).toBeVisible();
  expect(providerRequests).toEqual([]);
});
