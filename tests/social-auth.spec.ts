import { test, expect, continueReport } from './fixtures';

test('a delayed anonymous startup response preserves an opened signup form and its values', async ({ page }) => {
  let release: () => void = () => {};
  const waiting = new Promise<void>(resolve => { release = resolve; });
  await page.route('**/api/auth/me', async route => { await waiting; await route.fulfill({ status: 401, json: { message: 'Unauthenticated.' } }); });
  await page.goto('/');
  await page.getByRole('button', { name: 'Report a hazard', exact: true }).click(); await continueReport(page);
  const auth = page.getByRole('dialog', { name: 'Welcome to your community' });
  await auth.getByRole('button', { name: 'Create an account', exact: true }).click();
  await auth.getByLabel('Full name').fill('Pending Signup Citizen');
  const anonymousResponse = page.waitForResponse('**/api/auth/me');
  release();
  await anonymousResponse;
  await expect(page.locator('.connection')).toHaveText('Connected');
  await expect(auth).toBeVisible();
  await expect(auth.getByLabel('Full name')).toHaveValue('Pending Signup Citizen');
});

test('signup and login hide inactive provider actions and preserve email signup', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community' });
  await expect(auth.getByRole('button', { name: 'Continue with Google' })).toHaveCount(0);
  await expect(auth.getByRole('button', { name: 'Continue with Facebook' })).toHaveCount(0);
  await auth.getByRole('button', { name: 'Create an account', exact: true }).click();
  await expect(auth.getByLabel('Full name')).toBeVisible();
  await expect(auth.getByRole('button', { name: 'Continue with Google' })).toHaveCount(0);
  await expect(auth.getByRole('button', { name: 'Create your account', exact: true })).toBeEnabled();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await page.screenshot({ path: 'test-results/social-signup-mobile.png', fullPage: true });
});

for (const provider of ['google', 'facebook']) {
  test(`${provider} starts a server flow and cancellation returns a clear error`, async ({ page }) => {
    const attempt = '223b46ae-cd26-49da-9fab-acd4877f5d18';
    let submitted: Record<string, unknown> = {};
    await page.route('**/api/auth/providers', route => route.fulfill({ json: { google: { enabled: true }, facebook: { enabled: true } } }));
    await page.route(`**/api/auth/social/${provider}/attempts`, route => {
      submitted = route.request().postDataJSON();
      return route.fulfill({ status: 201, json: { attempt, redirect_url: new URL(`/auth/social/${provider}/redirect?attempt=${attempt}`, route.request().url()).href } });
    });
    await page.route(`**/auth/social/${provider}/redirect?*`, route => route.fulfill({ status: 302, headers: { location: `/?social_auth=error&social_error=cancelled&social_intent=report&provider=${provider}` } }));
    await page.goto('/');
    await page.getByRole('button', { name: 'Report a hazard', exact: true }).first().click(); await continueReport(page);
    const auth = page.getByRole('dialog', { name: 'Welcome to your community' });
    // Integration remains available, while entry buttons are intentionally hidden.
    await page.evaluate(async provider => { const integration = await import('/src/services/socialAuth.ts'); await integration.startSocialAuth(provider as 'google' | 'facebook', { intent: 'report' }); }, provider);
    await expect(auth.locator('.toast-message.error')).toContainText('Sign-in was cancelled');
    expect(submitted).toMatchObject({ platform: 'web', mode: 'login', intent: 'report', return_origin: new URL(page.url()).origin });
    expect(submitted).not.toHaveProperty('current_password');
    await expect(page).not.toHaveURL(/social_auth=/);
  });
}

test('a successful web return resumes reporting only with a real authenticated session', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community' });
  await auth.getByRole('button', { name: 'Create an account', exact: true }).click();
  await auth.getByLabel('Full name').fill('Social Return Citizen');
  await auth.getByLabel('Email address').fill(`social-return-${Date.now()}@example.test`);
  await auth.getByLabel('Password', { exact: true }).fill('isolated-social-return-password');
  await auth.getByRole('button', { name: 'Create your account', exact: true }).click();
  await expect(auth).toHaveCount(0);
  await page.goto('/?social_auth=success&social_intent=report&provider=google');
  await expect(page.getByRole('dialog', { name: 'Report a hazard', exact: true })).toBeVisible();
  expect(await page.evaluate(() => JSON.stringify(localStorage))).not.toContain('token');
  await expect(page).not.toHaveURL(/social_auth=/);
});

test('a forged web success query cannot authenticate a guest', async ({ page }) => {
  await page.goto('/?social_auth=success&social_intent=profile&provider=google');
  await expect(page.getByRole('dialog', { name: 'Welcome to your community' }).locator('.toast-message.error')).toContainText('expired');
  await expect(page.locator('.header-account')).toContainText('Community guest');
});

for (const cancelled of [false, true]) {
test(`Android cold launch ${cancelled ? 'shows provider cancellation without issuing a session' : 'exchanges its code using the vault verifier and retains only its session'}`, async ({ page, context }) => {
  const attempt = '223b46ae-cd26-49da-9fab-acd4877f5d18';
  const verifier = 'v'.repeat(43);
  const pending = { attempt, verifier, expiresAt: Date.now() + 60000 };
  const vault = new Map<string, string>();
  let exchanged = false;
  let pendingAvailable = true;
  const user = { id: 98989, name: 'Social Device Citizen', email: 'social-device@example.test', role: 'citizen', has_password: false };
  await context.exposeFunction('socialBridge', async (plugin: string, method: string, options: { prefixedKey: string; data?: string }) => {
    if (plugin === 'App' && method === 'getLaunchUrl') return { url: `ph.municipal.bantay://auth/callback?attempt=${attempt}&${cancelled ? 'social_auth=error&social_error=cancelled' : `social_auth=success&code=${'c'.repeat(64)}`}` };
    if (plugin === 'Browser' && method === 'close') return {};
    if (method === 'internalGetItem') {
      if (options.prefixedKey.endsWith('bantay.social.pending') && pendingAvailable) return { data: JSON.stringify(JSON.stringify(pending)) };
      return { data: vault.get(options.prefixedKey) ?? null };
    }
    if (method === 'internalSetItem') { vault.set(options.prefixedKey, options.data!); return; }
    if (method === 'internalRemoveItem') { if (options.prefixedKey.endsWith('bantay.social.pending')) pendingAvailable = false; vault.delete(options.prefixedKey); return { success: true }; }
    throw new Error(`Unexpected bridge call ${plugin}.${method}`);
  });
  await context.addInitScript(() => {
    Object.assign(window, { CapacitorCustomPlatform: { name: 'android' }, Capacitor: {
      PluginHeaders: [
        { name: 'SecureStorage', methods: ['internalGetItem', 'internalSetItem', 'internalRemoveItem'].map(name => ({ name, rtype: 'promise' })) },
        { name: 'App', methods: [{ name: 'getLaunchUrl', rtype: 'promise' }, { name: 'addListener', rtype: 'callback' }] },
        { name: 'Browser', methods: [{ name: 'close', rtype: 'promise' }] },
      ],
      nativeCallback: () => 'synthetic-listener',
      nativePromise: (plugin: string, method: string, options: unknown) => (window as unknown as { socialBridge: (plugin: string, method: string, options: unknown) => Promise<unknown> }).socialBridge(plugin, method, options),
    } });
  });
  await page.route('**/api/auth/social/exchange', route => {
    expect(route.request().postDataJSON()).toEqual({ attempt, code: 'c'.repeat(64), code_verifier: verifier });
    exchanged = true;
    return route.fulfill({ json: { user, token: 'synthetic-social-session', intent: 'report', linked: false } });
  });
  await page.route('**/api/auth/me', route => route.fulfill({ status: route.request().headers().authorization === 'Bearer synthetic-social-session' ? 200 : 401, json: user }));
  await page.route('**/api/notifications', route => route.fulfill({ json: [] }));
  await page.goto('/');
  if (cancelled) {
    await expect(page.locator('.toast')).toContainText('Sign-in was cancelled');
    await expect(page.locator('.header-account')).toContainText('Community guest');
    expect(exchanged).toBe(false);
    expect(pendingAvailable).toBe(false);
    expect(vault.size).toBe(0);
    return;
  }
  await expect(page.locator('.header-account')).toContainText(user.name);
  await expect(page.getByRole('dialog', { name: 'Report a hazard', exact: true })).toBeVisible();
  expect(exchanged).toBe(true);
  expect(vault.size).toBe(1);
  expect([...vault.values()].join()).not.toContain(verifier);
  expect(await page.evaluate(() => JSON.stringify(localStorage))).not.toContain('synthetic-social-session');
});
}
