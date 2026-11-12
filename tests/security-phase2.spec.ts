import { test, expect, finishAdminMfa, adminOtp, fillTwoFactor, navigateWorkspace } from './fixtures';
import type { Page } from '@playwright/test';

async function passwordLogin(page: Page) {
  await page.getByRole('button', { name: 'Account', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community' });
  await auth.getByLabel('Email address', { exact: true }).fill('admin@example.test');
  await auth.getByLabel('Password', { exact: true }).fill(process.env.E2E_ADMIN_PASSWORD!);
  await auth.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(auth.getByLabel('2FA code digit 1', { exact: true })).toBeVisible();
  return auth;
}

test('administrator password grants no access until a fresh second factor succeeds', async ({ page }) => {
  await page.goto('/');
  const auth = await passwordLogin(page);
  const denied = await page.request.get('/api/admin/reports'); expect(denied.status()).toBe(401);
  await auth.getByRole('button', { name: 'Use a recovery code', exact: true }).click();
  await auth.getByLabel('Recovery code', { exact: true }).fill('not-a-valid-code');
  await auth.getByRole('button', { name: 'Verify and sign in', exact: true }).click();
  await expect(auth.locator('.toast-message.error')).toContainText('Verification failed');
  await expect(auth.locator('.form-error')).toHaveCount(0);
  await finishAdminMfa(page);
  await expect(page.getByRole('heading', { name: 'Municipal dashboard', exact: true })).toBeVisible();
  await navigateWorkspace(page, 'Settings');
  await expect(page.locator('.account-security')).toContainText('Two-factor authentication (2FA) is enabled');
});

test('enrollment QR is generated locally and remains usable with large phone text', async ({ page }) => {
  await page.addInitScript(() => localStorage.setItem('munialert-reading-v1', JSON.stringify({ textSize: 200, reduceMotion: true })));
  await page.setViewportSize({ width: 375, height: 812 });
  await page.route('**/auth/login', route => route.fulfill({ json: { mfa_required: true, enrollment_required: true, challenge_token: '00000000-0000-4000-8000-000000000001.' + 'x'.repeat(64), expires_in: 300, secret: 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP', otpauth_uri: 'otpauth://totp/MuniAlert:synthetic?secret=JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP&issuer=MuniAlert' } }));
  await page.goto('/'); await passwordLogin(page);
  const auth = page.getByRole('dialog', { name: 'Welcome to your community' });
  await expect(auth.locator('.mfa-qr')).toHaveAttribute('src', /^data:image\/png;base64,/);
  await expect(auth.getByLabel('Private setup key', { exact: true })).toHaveAttribute('readonly', '');
  for (const width of [320, 375]) {
    await page.setViewportSize({ width, height: 812 });
    await expect.poll(() => auth.evaluate(el => el.scrollWidth <= el.clientWidth + 1)).toBe(true);
  }
});

test('step-up preserves the pending CMS edit, verifies identity and retries once', async ({ page }) => {
  await page.goto('/'); await passwordLogin(page); await finishAdminMfa(page);
  await page.getByRole('button', { name: 'Content management', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Municipal map & emergency hotlines' })).toBeVisible();
  let writes = 0;
  await page.route('**/api/admin/settings', async route => {
    if (route.request().method() !== 'PUT') return route.continue();
    writes++;
    if (writes === 1) return route.fulfill({ status: 428, json: { message: 'Verify your identity to continue.', reauthentication_required: true, password_required: true, mfa_required: true } });
    return route.continue();
  });
  await page.getByRole('button', { name: 'Save map and hotlines', exact: true }).click();
  await page.getByRole('dialog', { name: 'Publish CMS changes?' }).getByRole('button', { name: 'Publish changes', exact: true }).click();
  const proof = page.getByRole('dialog', { name: 'Verify your identity', exact: true });
  await proof.getByLabel('Current password', { exact: true }).fill(process.env.E2E_ADMIN_PASSWORD!);
  await proof.getByRole('button', { name: 'Show current password', exact: true }).click();
  await expect(proof.getByLabel('Current password', { exact: true })).toHaveAttribute('type', 'text');
  await proof.getByRole('button', { name: 'Hide current password', exact: true }).click();
  await expect(proof.getByLabel('Current password', { exact: true })).toHaveAttribute('type', 'password');
  await proof.getByRole('button', { name: 'Use a recovery code', exact: true }).click();
  await proof.getByLabel('Recovery code', { exact: true }).fill(process.env.E2E_ADMIN_RECOVERY_CODE!);
  await proof.getByRole('button', { name: 'Verify identity', exact: true }).click();
  await expect(proof).toHaveCount(0);
  await expect(page.locator('.toast-message.success').filter({ hasText: 'Map and hotlines saved.' })).toBeVisible();
  expect(writes).toBe(2);
});

test('recovery fragments are removed before requests and recovery does not reveal account existence', async ({ page }) => {
  const token = '00000000-0000-4000-8000-000000000001.' + 'x'.repeat(64);
  const requests: string[] = []; page.on('request', request => requests.push(request.url()));
  await page.goto('/#reset=' + token);
  await expect(page).not.toHaveURL(/#reset/);
  await expect(page.getByRole('heading', { name: 'Choose a new password', exact: true })).toBeVisible();
  expect(requests.some(url => url.includes(token))).toBe(false);
  await page.getByRole('button', { name: 'Back to sign in', exact: true }).click();
  await page.getByRole('button', { name: 'Forgot your password?', exact: true }).click();
  await page.getByLabel('Email address', { exact: true }).fill('unknown@example.test');
  await page.getByRole('button', { name: 'Request recovery link', exact: true }).click();
  await expect(page.locator('.security-form [role=status]')).toContainText('If this account is eligible');
});

test('packaged web CSP runs the app and blocks injected inline script', async ({ page }) => {
  const origin = `http://127.0.0.1:${process.env.HAZARD_API_PORT || '18000'}`;
  await page.addInitScript(() => {
    (window as any).policyViolations = [];
    document.addEventListener('securitypolicyviolation', event => (window as any).policyViolations.push(event.violatedDirective));
  });
  const response = await page.goto(origin + '/?demo=1');
  expect(response?.headers()['content-security-policy']).toContain("script-src 'self'");
  await expect(page.getByRole('heading', { name: 'Community dashboard', exact: true })).toBeVisible();
  await expect(page.locator('.maplibregl-canvas')).toBeVisible();
  expect(await page.evaluate(() => (window as any).policyViolations)).toEqual([]);
  await page.evaluate(() => { const script = document.createElement('script'); script.textContent = 'window.inlineScriptExecuted = true'; document.body.append(script); });
  await expect.poll(() => page.evaluate(() => (window as any).policyViolations)).toContain('script-src-elem');
  expect(await page.evaluate(() => (window as any).inlineScriptExecuted)).toBeUndefined();
});

test('two competing MFA submissions can issue only one credential', async ({ request }) => {
  const origin = `http://127.0.0.1:${process.env.HAZARD_API_PORT || '18000'}`;
  const pending = await request.post(origin + '/api/auth/login', { headers: { Accept: 'application/json' }, data: { email: 'admin@example.test', password: process.env.E2E_ADMIN_PASSWORD } });
  expect(pending.ok()).toBe(true); const challenge = await pending.json();
  const data = { challenge_token: challenge.challenge_token, code: adminOtp() };
  // MySQL mode starts a second independent PHP process. SQLite's ordinary gate
  // also checks single consumption, but its one HTTP worker runs sequentially.
  const results = await Promise.all([origin, process.env.HAZARD_E2E_PEER_URL || origin].map(server => request.post(server + '/api/auth/mfa/verify', { headers: { Accept: 'application/json' }, data })));
  expect(results.map(result => result.status()).sort()).toEqual([200, 422]);
  expect((await Promise.all(results.map(result => result.json()))).filter(result => result.token)).toHaveLength(1);
});

test('closing MFA while its response is delayed does not leave an authenticated browser', async ({ page }) => {
  await page.goto('/'); const auth = await passwordLogin(page);
  let release!: () => void; let ready!: () => void;
  const held = new Promise<void>(resolve => { release = resolve; });
  const received = new Promise<void>(resolve => { ready = resolve; });
  await page.route('**/auth/mfa/verify', async route => {
    const response = await route.fetch(); ready(); await held; await route.fulfill({ response });
  });
  await fillTwoFactor(page, adminOtp());
  await auth.getByRole('button', { name: 'Verify and sign in', exact: true }).click();
  await received;
  await auth.getByRole('button', { name: 'Close dialog', exact: true }).click();
  const logout = page.waitForResponse(response => response.url().endsWith('/api/auth/logout'));
  release(); await logout;
  await expect(auth).toHaveCount(0);
  const me = await page.request.get('/api/auth/me', { headers: { Accept: 'application/json' } });
  expect(me.status()).toBe(401);
  await expect(page.locator('.sidebar-user')).toContainText('Community guest');
});
