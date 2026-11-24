import { test, expect, accountOption, navigateWorkspace } from './fixtures';

test('dashboard greets in Philippine time and keeps municipality context out of the sidebar', async ({ page }) => {
  await page.clock.install({ time: new Date('2026-11-16T02:29:34Z') });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto('/?demo=1');
  await expect(page.locator('h1')).toHaveText('Magandang umaga, ka-bayan!');
  await expect(page.locator('.dashboard-municipality')).toContainText('Basista Municipal Hall');
  await expect(page.locator('.dashboard-municipality')).toContainText('Community safety workspace');
  await expect(page.locator('.sidebar .workspace')).toHaveCount(0);
  await expect(page.locator('.community-visibility-note')).toHaveCount(0);
  const clock = page.locator('.topbar .ph-clock');
  await expect(clock).toHaveText('Mon, Nov 16, 10:29:34 AM PHT');
  await page.clock.fastForward(1000);
  await expect(clock).toContainText('10:29:35 AM');
  await page.clock.setSystemTime(new Date('2026-11-16T07:00:00Z'));
  await page.clock.runFor(1000);
  await expect(page.locator('h1')).toHaveText('Magandang hapon, ka-bayan!');
  await page.screenshot({ path: 'test-results/dashboard-welcome-desktop.png', animations: 'disabled' });
});

test('info explanations support hover, focus, Escape and tap across pages', async ({ page }) => {
  await page.goto('/?demo=1');
  const info = page.getByRole('button', { name: 'About community dashboard', exact: true });
  await info.hover();
  await expect(page.getByRole('tooltip')).toContainText('Only hazards fact-checked and accepted');
  await page.getByRole('tooltip').hover();
  await expect(page.getByRole('tooltip')).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(page.getByRole('tooltip')).toHaveCount(0);
  await info.focus();
  await expect(page.getByRole('tooltip')).toContainText('Track your private submissions in My reports.');
  await page.keyboard.press('Escape');
  await expect(info).toBeFocused();
  await navigateWorkspace(page, 'Hazard map');
  await page.getByRole('button', { name: 'About community hazard map', exact: true }).click();
  await expect(page.getByRole('tooltip')).toContainText('Only hazards fact-checked');
  await accountOption(page, 'Settings');
  await page.getByRole('button', { name: 'About settings', exact: true }).click();
  await expect(page.getByRole('tooltip')).toContainText('account security');
  await page.getByRole('heading', { name: 'Settings', exact: true }).click();
  await expect(page.getByRole('tooltip')).toHaveCount(0);
});

test('phone header and info tooltip reflow with enlarged text and touch input', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 900 });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.addInitScript(() => localStorage.setItem('munialert-reading-v1', JSON.stringify({ textSize: 200 })));
  await page.goto('/?demo=1');
  await expect(page.locator('.topbar .ph-clock')).toBeVisible();
  expect((await page.locator('.topbar .ph-clock').boundingBox())!.width).toBeGreaterThan(280);
  const brand = (await page.locator('.topbar .breadcrumb').boundingBox())!;
  const actions = (await page.locator('.topbar .top-actions').boundingBox())!;
  expect(brand.y + brand.height).toBeLessThanOrEqual(actions.y);
  await expect(page.locator('.mobile-clock')).toHaveCount(0);
  const info = page.getByRole('button', { name: 'About community dashboard', exact: true });
  await info.click();
  const tip = page.getByRole('tooltip');
  await expect(tip).toContainText('Track your private submissions');
  const box = await tip.boundingBox();
  expect(box!.x).toBeGreaterThanOrEqual(0);
  expect(box!.x + box!.width).toBeLessThanOrEqual(320);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await page.screenshot({ path: 'test-results/dashboard-welcome-mobile-large.png', animations: 'disabled' });
});

test('synthetic resident signs in and receives a personalized greeting', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community' });
  await auth.getByLabel('Email address').fill('resident1@munialert.test');
  await auth.getByLabel('Password', { exact: true }).fill(process.env.E2E_CITIZEN_PASSWORD!);
  await auth.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page.locator('h1')).toHaveText(/Magandang (umaga|hapon|gabi), Synthetic!/);
  await expect(page.locator('.header-account')).toContainText('Synthetic Resident 1');
});
