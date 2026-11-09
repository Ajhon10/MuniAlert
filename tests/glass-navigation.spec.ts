import { test, expect } from './fixtures';
import { demoReports } from '../hazard-client/src/data/demo';

test('phone dock navigates, opens reporting, and browser back restores the view', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/?demo=1');
  const dock = page.getByRole('navigation', { name: 'Quick navigation' });
  await expect(dock).toBeVisible();
  await expect(dock.getByRole('button')).toHaveCount(5);
  await dock.getByRole('button', { name: 'Map', exact: true }).click();
  await expect(page.locator('h1')).toHaveText('Community hazard map');
  await expect(page).toHaveURL(/view=map/);
  await page.goBack();
  await expect(page.locator('h1')).toHaveText('Community overview');
  await dock.getByRole('button', { name: 'Create hazard report' }).click();
  await expect(page.getByRole('dialog', { name: 'Report a hazard', exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Close dialog' }).click();
  await page.getByRole('dialog', { name: 'Discard this report?' }).getByRole('button', { name: 'Discard report', exact: true }).click();
  await expect(page.getByRole('dialog')).toHaveCount(0);
  await dock.getByRole('button', { name: 'More navigation' }).click();
  await page.getByRole('button', { name: 'Verified news', exact: true }).click();
  await expect(page.locator('.news-card')).not.toHaveCount(0);
  await expect(page.locator('.news-card button').first()).toBeDisabled();
  await expect(page.locator('.news-intro')).toContainText('Fictional');
  await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await page.screenshot({ path: 'test-results/glass-news-mobile.png', fullPage: true });
});

test('packaged web client signs in through the same Laravel origin', async ({ page }) => {
  const origin = `http://127.0.0.1:${process.env.HAZARD_API_PORT || '18000'}`;
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto(origin);
  await expect(page.locator('h1')).toHaveText('Community overview');
  const script = await page.locator('script[type=module]').getAttribute('src');
  expect(script).toMatch(/^\/app\/assets\//);
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community' });
  await auth.getByLabel('Email address').fill('itsajhon@gmail.com');
  await auth.getByLabel('Password').fill(process.env.E2E_ADMIN_PASSWORD!);
  await auth.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(auth).toHaveCount(0);
  await expect(page.locator('h1')).toHaveText('Municipal operations');
  await page.reload();
  await expect(page.locator('.sidebar-user')).toContainText('Municipal administrator');
  const cookie = (await page.context().cookies()).find(item => item.name.includes('session'));
  expect(cookie?.httpOnly).toBe(true);
  expect(errors).toEqual([]);
});

test('sign-in resumes the protected destination selected by a guest', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('button', { name: 'Notifications', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community' });
  await auth.getByLabel('Email address').fill('citizen.one@example.test');
  await auth.getByLabel('Password').fill(process.env.E2E_CITIZEN_PASSWORD!);
  await auth.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(auth).toHaveCount(0);
  await expect(page.locator('h1')).toHaveText('Notifications');
  await expect(page).toHaveURL(/view=activity/);
});

test('community comments load subsequent pages without duplicates and display an error on failure', async ({ page }) => {
  const report = demoReports().find(report => report.status === 'verified')!;
  const comments = Array.from({ length: 30 }, (_, index) => ({ id: index + 1, user_id: 999, name: 'Synthetic citizen', body: `Synthetic page comment ${index + 1}`, created_at: new Date().toISOString() }));
  await page.route('**/api/news?*', route => route.fulfill({ json: { data: [{ report, reactions: {}, comments_count: 30, my_reaction: null }], meta: { last_page: 1 } } }));
  let failNext = true;
  await page.route(`**/api/news/${report.id}/comments*`, route => {
    const after = Number(new URL(route.request().url()).searchParams.get('after') || 0);
    if (after && failNext) { failNext = false; return route.fulfill({ status: 503, json: { message: 'Synthetic connection interruption. Try again.' } }); }
    return route.fulfill({ json: { data: comments.slice(after, after + 25), meta: { has_more: after + 25 < 30, next_cursor: Math.min(30, after + 25) } } });
  });
  await page.goto('/?view=news');
  await page.getByRole('button', { name: 'Comments 30', exact: true }).click();
  await expect(page.locator('.comment')).toHaveCount(25);
  await page.getByRole('button', { name: 'Load more comments' }).click();
  await expect(page.getByRole('alert')).toContainText('Synthetic connection interruption');
  await expect(page.locator('.comment')).toHaveCount(25);
  await page.getByRole('button', { name: 'Load more comments' }).click();
  await expect(page.locator('.comment')).toHaveCount(30);
  await expect(page.getByRole('button', { name: 'Load more comments' })).toHaveCount(0);
});
