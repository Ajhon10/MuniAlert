import { test, expect, accountOption } from './fixtures';

test('internet outage stays visible while scrolling and recovers automatically', async ({ page, context }) => {
  await page.goto('/');
  await expect(page.locator('.page-footer')).toContainText('Last synced');
  await page.clock.install();
  await context.setOffline(true);
  const banner = page.locator('.connection-banner-shell');
  await expect(banner.locator('strong')).toHaveText('Not connected to the internet');
  await page.clock.fastForward(20000);
  await expect(banner).toHaveClass(/visible/);
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
  await expect.poll(async () => (await banner.boundingBox())?.y).toBe(0);
  await page.screenshot({ path: 'test-results/connection-offline-desktop.png' });
  // Keep the three-second recovery notice observable even when the real
  // health request is slow; advance its dismissal clock explicitly below.
  await page.clock.pauseAt(await page.evaluate(() => Date.now() + 1000));
  await context.setOffline(false);
  await expect(banner.locator('strong')).toHaveText('Connected');
  await page.clock.fastForward(2000);
  await expect(banner).toHaveClass(/visible/);
  await page.clock.fastForward(2000);
  await expect(banner).not.toHaveClass(/visible/);
  await expect.poll(async () => (await banner.boundingBox())?.height).toBe(0);
});

test('server outage persists across navigation and retries, with specific HTTP feedback', async ({ page }) => {
  let broken = false;
  await page.route('**/api/health', route => route.fulfill({ status: broken ? 503 : 200, json: { status: broken ? 'unavailable' : 'ok' } }));
  await page.route('**/api/stats', route => broken ? route.fulfill({ status: 503, json: { message: 'Service unavailable' } }) : route.continue());
  await page.goto('/');
  await expect(page.locator('.page-footer')).toContainText('Last synced');
  broken = true;
  await page.getByRole('button', { name: 'Refresh reports', exact: true }).click();
  const banner = page.locator('.connection-banner-shell');
  await expect(banner.locator('strong')).toHaveText('Not connected to the municipal server');
  await expect(page.locator('.server-error')).toContainText('HTTP 503');
  await page.getByRole('button', { name: 'Retry connection', exact: true }).click();
  await expect(banner.locator('strong')).toHaveText('Not connected to the municipal server');
  await accountOption(page, 'Settings');
  await expect(page.getByRole('heading', { name: 'Settings', exact: true })).toBeVisible();
  await expect(banner).toHaveClass(/visible/);
  await page.screenshot({ path: 'test-results/connection-server-desktop.png' });
  broken = false;
  await page.getByRole('button', { name: 'Retry connection', exact: true }).click();
  await expect(banner.locator('strong')).toHaveText('Connected');
  await expect(page.locator('.server-error')).toHaveCount(0);
  await expect(banner).not.toHaveClass(/visible/);
});

test('mobile outage banner reflows at 200% text and respects reduced motion', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 740 });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.addInitScript(() => localStorage.setItem('munialert-reading-v1', JSON.stringify({ textSize: 200 })));
  await page.route('**/api/health', route => route.fulfill({ status: 503, json: { status: 'unavailable' } }));
  await page.goto('/');
  await expect(page.locator('html')).toHaveAttribute('data-text-size', '200');
  const banner = page.locator('.connection-banner-shell');
  await expect(banner.locator('strong')).toHaveText('Not connected to the municipal server');
  await expect(page.getByRole('button', { name: 'Retry connection', exact: true })).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  expect(await banner.evaluate(element => getComputedStyle(element).transitionDuration)).toBe('0s');
  await expect.poll(async () => {
    const header = await page.locator('.topbar').boundingBox();
    const box = await banner.boundingBox();
    return !!header && !!box && header.y >= box.y + box.height - 1;
  }).toBe(true);
  await page.screenshot({ path: 'test-results/connection-server-mobile-large.png' });
});

test('offline sign-in preserves input and explains the missing internet connection', async ({ page, context }) => {
  await page.goto('/');
  await expect(page.locator('.page-footer')).toContainText('Last synced');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const dialog = page.getByRole('dialog', { name: 'Welcome to your community' });
  await dialog.getByLabel('Email address').fill('offline@example.test');
  await dialog.getByLabel('Password', { exact: true }).fill('synthetic-offline-password');
  await context.setOffline(true);
  await dialog.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(dialog.locator('.toast-message.error')).toContainText('Not connected to the internet');
  await expect(dialog.getByLabel('Email address')).toHaveValue('offline@example.test');
  await expect(dialog.getByLabel('Password', { exact: true })).toHaveValue('synthetic-offline-password');
});

test('transport failures and timeouts have specific messages', async ({ page }) => {
  await page.addInitScript(() => {
    const realFetch = window.fetch.bind(window);
    Object.assign(window, { connectionTestFailure: '' });
    window.fetch = async (input, init) => {
      const mode = (window as unknown as { connectionTestFailure: string }).connectionTestFailure;
      if (String(input).includes('/api/stats') && mode) throw mode === 'timeout' ? new DOMException('Synthetic timeout', 'TimeoutError') : new TypeError('Synthetic network failure');
      return realFetch(input, init);
    };
  });
  await page.goto('/');
  await expect(page.locator('.page-footer')).toContainText('Last synced');
  await page.evaluate(() => Object.assign(window, { connectionTestFailure: 'network' }));
  await page.getByRole('button', { name: 'Refresh reports', exact: true }).click();
  await expect(page.locator('.server-error')).toContainText('Not connected to the municipal server');
  await page.evaluate(() => Object.assign(window, { connectionTestFailure: 'timeout' }));
  await page.getByRole('button', { name: 'Refresh reports', exact: true }).click();
  await expect(page.locator('.server-error')).toContainText('took too long to respond');
});

test('field validation retains the server message without marking the connection unavailable', async ({ page }) => {
  await page.route('**/auth/login', route => route.fulfill({ status: 422, json: { message: 'Invalid input.', errors: { email: ['This email address is not registered.'] } } }));
  await page.goto('/');
  await expect(page.locator('.page-footer')).toContainText('Last synced');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const dialog = page.getByRole('dialog', { name: 'Welcome to your community' });
  await dialog.getByLabel('Email address').fill('unregistered@example.test');
  await dialog.getByLabel('Password', { exact: true }).fill('synthetic-validation-password');
  await dialog.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(dialog.locator('.toast-message.error')).toContainText('This email address is not registered.');
  await expect(dialog.getByLabel('Email address')).toHaveAttribute('aria-invalid', 'true');
  await expect(page.locator('.connection-banner.server-unavailable')).toHaveCount(0);
});

test('an invalid health response cannot claim recovery and a renewed outage cancels dismissal', async ({ page, context }) => {
  let valid = false;
  await page.route('**/api/health', route => route.fulfill(valid ? { json: { status: 'ok' } } : { contentType: 'text/html', body: '<h1>Proxy error</h1>' }));
  await page.goto('/');
  const banner = page.locator('.connection-banner-shell');
  await expect(banner.locator('strong')).toHaveText('Not connected to the municipal server');
  await page.clock.install();
  valid = true;
  await page.getByRole('button', { name: 'Retry connection', exact: true }).click();
  await expect(banner.locator('strong')).toHaveText('Connected');
  await context.setOffline(true);
  await expect(banner.locator('strong')).toHaveText('Not connected to the internet');
  await page.clock.fastForward(5000);
  await expect(banner).toHaveClass(/visible/);
});

test('demo preview does not claim a live connection', async ({ page }) => {
  let checks = 0;
  await page.route('**/api/health', route => { checks++; return route.fulfill({ json: { status: 'ok' } }); });
  await page.goto('/?demo=1');
  await expect(page.locator('.demo-banner')).toContainText('Fictional reports');
  await expect(page.locator('.connection-banner-shell')).not.toHaveClass(/visible/);
  expect(checks).toBe(0);
});
