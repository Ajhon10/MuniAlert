import { test, expect } from './fixtures';

const title = 'Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking';

test('MuniAlert branding and the complete title are readable in the public phone manual', async ({ page }) => {
  await page.setViewportSize({ width: 360, height: 800 });
  await page.goto('/?demo=1&view=guide');
  await expect(page).toHaveTitle(/MuniAlert/);
  await expect(page.locator('.topbar .breadcrumb')).toContainText('MuniAlert');
  await expect(page.getByRole('heading', { name: 'Guide & user manual', exact: true })).toBeVisible();
  await page.getByText('About this capstone', { exact: true }).click();
  await expect(page.locator('.guide-project-title')).toContainText(title);
  await expect(page.getByRole('button', { name: 'Citizen guide', exact: true })).toHaveAttribute('aria-pressed', 'true');
  await page.getByRole('button', { name: 'Read instructions: Private original evidence', exact: true }).click();
  await expect(page.locator('#manual-privacy')).toHaveAttribute('open', '');
  await expect(page.locator('#manual-privacy')).toContainText('private to the reporter and authorized municipal administrators');
  for (const width of [320, 360, 390, 768, 1440]) {
    await page.setViewportSize({ width, height: 800 });
    await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  }
  await page.setViewportSize({ width: 390, height: 800 });
  await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
  await expect.poll(() => page.evaluate(() => window.scrollY)).toBe(0);
  await page.screenshot({ path: 'test-results/munialert-guide-phone.png', fullPage: true, animations: 'disabled' });
});

test('manual search, audience selection and no-match recovery preserve useful instructions', async ({ page }) => {
  await page.goto('/?demo=1&view=guide');
  await page.getByRole('button', { name: 'Administrator guide', exact: true }).click();
  await page.getByRole('button', { name: 'Read instructions: Weekly and monthly records', exact: true }).click();
  await expect(page.locator('#manual-analytics')).toHaveAttribute('open', '');
  await expect(page.locator('#manual-analytics')).toContainText('Weeks start Monday');
  await expect(page.locator('#manual-analytics')).toContainText('Counts describe reports');
  const search = page.getByRole('searchbox', { name: 'Search user manual', exact: true });
  await search.fill('unlikely-no-matching-instruction');
  await expect(page.getByRole('heading', { name: 'No matching instructions', exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Clear manual search', exact: true }).click();
  await search.fill('private');
  await expect(page.locator('#manual-privacy')).toBeVisible();
  await expect(page.locator('#manual-privacy')).toHaveAttribute('open', '');
  await page.getByRole('button', { name: 'Citizen guide', exact: true }).click();
  await search.fill('GPS');
  await expect(page.locator('#manual-location')).toBeVisible();
  await expect(page.locator('#manual-location')).toContainText('If GPS is denied or unavailable');
  await expect(page.locator('#manual-analytics')).toHaveCount(0);
});

test('highlighted feature tour covers the phone controls and returns to the manual', async ({ page }) => {
  const errors: string[] = []; page.on('pageerror', error => errors.push(error.message));
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/?demo=1&view=guide');
  await page.getByRole('button', { name: 'Start feature tour', exact: true }).click();
  const tour = page.getByRole('dialog', { name: 'Feature tour', exact: true });
  await expect(tour).toBeVisible();
  await expect(tour.getByRole('button', { name: 'Back', exact: true })).toBeDisabled();
  for (let step = 1; step < 6; step++) {
    await expect(tour).toContainText(`${step} of 6`);
    const focus = await tour.locator('.tour-focus').boundingBox();
    expect(focus!.width).toBeGreaterThan(0); expect(focus!.height).toBeGreaterThan(0);
    expect(focus!.x).toBeGreaterThanOrEqual(0); expect(focus!.x + focus!.width).toBeLessThanOrEqual(390);
    if (step === 2) await page.screenshot({ path: 'test-results/munialert-tour-phone.png', animations: 'disabled' });
    await tour.getByRole('button', { name: 'Next', exact: true }).click();
  }
  await expect(tour).toContainText('6 of 6');
  await tour.getByRole('button', { name: 'Open user manual', exact: true }).click();
  await expect(tour).toHaveCount(0);
  await expect(page).toHaveURL(/view=guide/);
  await expect(page.getByRole('heading', { name: 'Guide & user manual', exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Start feature tour', exact: true }).click();
  await expect(tour).toBeVisible();
  await page.keyboard.press('Escape'); await expect(tour).toHaveCount(0);
  expect(errors).toEqual([]);
});

test('the bundled manual stays readable when the API is unavailable', async ({ page }) => {
  await page.route('**/api/**', route => route.abort());
  await page.goto('/?view=guide');
  await expect(page.getByRole('heading', { name: 'Guide & user manual', exact: true })).toBeVisible();
  await page.getByRole('searchbox', { name: 'Search user manual', exact: true }).fill('connection');
  await expect(page.locator('#manual-troubleshooting')).toBeVisible();
  await expect(page.locator('#manual-troubleshooting')).toContainText('Unsent forms are not automatically saved');
});

test('sign-in from the guide and an authenticated provider return preserve the manual destination', async ({ page }) => {
  await page.goto('/?view=guide');
  await page.getByRole('button', { name: 'Account', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community' });
  await auth.getByRole('button', { name: 'Create an account', exact: true }).click();
  await auth.getByLabel('Full name').fill('Guide Return Citizen');
  await auth.getByLabel('Email address').fill(`guide-${Date.now()}@example.test`);
  await auth.getByLabel('Password', { exact: true }).fill('isolated-guide-return-password');
  await auth.getByRole('button', { name: 'Create your account', exact: true }).click();
  await expect(auth).toHaveCount(0);
  await expect(page.getByRole('heading', { name: 'Guide & user manual', exact: true })).toBeVisible();
  await page.goto('/?social_auth=success&social_intent=guide&provider=google');
  await expect(page.getByRole('heading', { name: 'Guide & user manual', exact: true })).toBeVisible();
  await expect(page).toHaveURL(/view=guide/);
  await expect(page).not.toHaveURL(/social_auth=/);
});
