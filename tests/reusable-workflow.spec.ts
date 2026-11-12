import { test, expect, finishAdminMfa, accountOption } from './fixtures';
import type { Page } from '@playwright/test';
import { demoReports } from '../hazard-client/src/data/demo';

async function login(page: Page, admin = false) {
  await page.goto('/');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community', exact: true });
  await auth.getByLabel('Email address', { exact: true }).fill(admin ? 'admin@example.test' : 'citizen.two@example.test');
  await auth.getByLabel('Password', { exact: true }).fill(admin ? process.env.E2E_ADMIN_PASSWORD! : process.env.HAZARD_DEMO_CITIZEN_TWO_PASSWORD!);
  await auth.getByRole('button', { name: 'Sign in', exact: true }).click();
  if (admin) await finishAdminMfa(page);
  await expect(auth).toHaveCount(0);
}

test('shared notification rows clamp long previews and open the full authorized report without a redundant footer', async ({ page }) => {
  const report = { ...demoReports()[0]!, id: '12345678-1234-4234-8234-123456789012', description: 'Full private notice destination content for its authorized owner.', photos: [], history: [] };
  const notices = ['A long verified update. '.repeat(80), 'A short update.'].map((message, i) => ({ id: `notice-${i}`, data: { report_id: report.id, reference_number: 'HZ-SYNTHETIC', message }, read_at: null, created_at: new Date().toISOString() }));
  await page.route('**/api/notifications', route => route.fulfill({ json: notices }));
  await page.route(`**/api/reports/${report.id}`, route => route.fulfill({ json: { data: report } }));
  await page.route('**/api/notifications/*/read', route => route.fulfill({ status: 204 }));
  await login(page);
  await page.getByRole('button', { name: 'Open notifications', exact: true }).first().click();
  const inbox = page.getByRole('dialog', { name: 'Notifications', exact: true });
  await expect(inbox.locator('.modal-footer')).toHaveCount(0);
  const rows = inbox.locator('.notification-row'); await expect(rows).toHaveCount(2);
  for (const size of [100, 200]) {
    const zoom = await page.addStyleTag({ content: `html { font-size: ${size}% !important; }` });
    await page.setViewportSize({ width: size === 200 ? 375 : 1280, height: 900 });
    expect((await rows.nth(0).boundingBox())!.height).toBeCloseTo((await rows.nth(1).boundingBox())!.height, 0);
    await expect.poll(async () => Math.abs((await rows.first().boundingBox())!.height - (await inbox.locator('.modal-header').boundingBox())!.height)).toBeLessThan(1);
    expect(await rows.first().locator('p').evaluate(el => el.scrollHeight > el.clientHeight)).toBe(true);
    expect(await inbox.evaluate(el => el.scrollWidth <= el.clientWidth + 1)).toBe(true);
    await zoom.evaluate(el => el.remove());
  }
  await page.screenshot({ path: 'test-results/notification-previews-fixed.png' });
  await rows.first().click();
  await expect(inbox).toHaveCount(0);
  const detail = page.getByRole('dialog', { name: 'Hazard report', exact: true });
  await expect(detail).toContainText(report.description);
  await expect(page.getByRole('heading', { name: 'My reports', exact: true })).toBeVisible();
});

test('expanding one achievement card leaves its neighboring cards at their original height', async ({ page }) => {
  await page.setViewportSize({ width: 1600, height: 1000 }); await login(page);
  await accountOption(page, 'My profile');
  const cards = page.locator('.hazard-achievement-track'); await expect(cards).toHaveCount(10);
  const neighbor = (await cards.nth(1).boundingBox())!;
  const closed = (await cards.first().boundingBox())!;
  await cards.first().locator('summary').click();
  expect((await cards.first().boundingBox())!.height).toBeGreaterThan(closed.height);
  expect((await cards.nth(1).boundingBox())!.height).toBeCloseTo(neighbor.height, 0);
  await page.screenshot({ path: 'test-results/achievement-independent-expansion.png', fullPage: true });
});

test('department management creates offices and changes category routing through shared controls', async ({ page }) => {
  await login(page, true); await page.getByRole('button', { name: 'Content management', exact: true }).click();
  await page.getByRole('button', { name: 'Add department', exact: true }).click();
  const modal = page.getByRole('dialog', { name: 'Add department', exact: true });
  await modal.getByLabel('Office identifier', { exact: true }).fill('synthetic-health');
  await modal.getByLabel('Office name', { exact: true }).fill('Synthetic Health Office');
  await modal.getByRole('button', { name: 'Save department', exact: true }).click();
  await expect(modal).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Edit Synthetic Health Office', exact: true })).toBeVisible();
  const routing = page.getByLabel('Responsible office for Other hazard', { exact: true });
  await routing.selectOption('synthetic-health');
  await routing.locator('xpath=ancestor::form').getByRole('button', { name: 'Save routing', exact: true }).click();
  await expect(routing.locator('xpath=ancestor::form').getByRole('button', { name: 'Save routing', exact: true })).toBeDisabled();
  const catalog = await page.evaluate(async () => (await fetch('/api/catalog')).json());
  expect(catalog.categories.find((c: any) => c.id === 'other').department_id).toBe('synthetic-health');
  await page.screenshot({ path: 'test-results/department-management.png', fullPage: true });
  await routing.selectOption('general');
  await routing.locator('xpath=ancestor::form').getByRole('button', { name: 'Save routing', exact: true }).click();
});

test('sharing prepares reviewed report content while keeping private evidence out and explaining localhost', async ({ page, context }) => {
  await context.grantPermissions(['clipboard-read', 'clipboard-write']);
  const report = { ...demoReports()[0]!, title: 'Reviewed synthetic hazard', description: 'Public reviewed incident summary.', public_reporter_name: 'Approved Sample Name', address: 'PRIVATE address excluded from sharing' };
  await page.route('**/api/news?*', route => route.fulfill({ json: { data: [{ report, reactions: {}, comments_count: 0, my_reaction: null }], meta: { last_page: 1 } } }));
  await page.goto('/?view=news');
  await page.getByRole('button', { name: 'Facebook', exact: true }).click();
  const share = page.getByRole('dialog', { name: 'Share verified news', exact: true });
  await expect(share).toContainText('localhost');
  const prepared = share.getByLabel('Prepared share post', { exact: true });
  await expect(prepared).toHaveValue(/Reviewed synthetic hazard/);
  const text = await prepared.inputValue(); expect(text).toContain('Public reviewed incident summary.'); expect(text).toContain('Approved Sample Name'); expect(text).not.toContain('PRIVATE');
  await share.getByRole('button', { name: 'Copy post text', exact: true }).click();
  expect((await page.evaluate(() => navigator.clipboard.readText())).replaceAll('\r\n', '\n')).toBe(text);
  await expect(share.getByRole('link', { name: 'Open Facebook', exact: true })).toHaveAttribute('href', /facebook\.com\/sharer\/sharer\.php\?u=/);
});

test('forgot-password exposes both methods and submits saved-code errors as backend JSON', async ({ page }) => {
  await page.goto('/'); await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community', exact: true });
  await auth.getByRole('button', { name: 'Forgot your password?', exact: true }).click();
  await expect(auth.locator('.modal-footer')).toHaveCount(0);
  await expect(auth.getByRole('button', { name: 'Send email reset link', exact: true })).toBeVisible();
  await auth.getByRole('button', { name: 'Use recovery code', exact: true }).click();
  await auth.getByLabel('Email address', { exact: true }).fill('unknown@example.test');
  await auth.getByLabel('Saved recovery code', { exact: true }).fill('aaaaaaaaaa-bbbbbbbbbb');
  await auth.getByLabel('New password', { exact: true }).fill('synthetic-new-password');
  await auth.getByLabel('Confirm new password', { exact: true }).fill('synthetic-new-password');
  const response = page.waitForResponse('**/api/auth/recovery/code');
  await auth.getByRole('button', { name: 'Reset password with code', exact: true }).click();
  const result = await response; expect(result.status()).toBe(422); expect((await result.json()).errors.code).toBeTruthy();
  await expect(auth.locator('.toast-message.error')).toContainText('Recovery could not be completed');
});
