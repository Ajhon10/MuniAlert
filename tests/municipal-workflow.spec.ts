import { test, expect, finishAdminMfa, adminApiToken, reviewIncidentImages } from './fixtures';
import type { Page } from '@playwright/test';

async function signIn(page: Page, email: string, password: string) {
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  const dialog = page.getByRole('dialog', { name: 'Welcome to your community' });
  await dialog.getByLabel('Email address').fill(email);
  await dialog.getByLabel('Password', { exact: true }).fill(password);
  await dialog.getByRole('button', { name: 'Sign in', exact: true }).click();
  if (email === 'admin@example.test') await finishAdminMfa(page);
  await expect(dialog).toHaveCount(0);
}

test('municipal lifecycle reaches the citizen inbox, profile edits persist, and unverified deletion works', async ({ page, request }) => {
  test.setTimeout(120000);
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  const origin = `http://127.0.0.1:${process.env.HAZARD_API_PORT || '18000'}`;
  const email = `workflow-${Date.now()}@example.test`;
  const password = 'isolated-municipal-workflow-password';
  const registration = await request.post(`${origin}/api/auth/register`, {
    headers: { Accept: 'application/json' }, data: { name: 'Workflow Citizen', email, password },
  });
  expect(registration.status()).toBe(201);
  const token = (await registration.json()).token as string;
  await page.goto('/');
  const image = await page.evaluate(() => {
    const canvas = document.createElement('canvas'); canvas.width = canvas.height = 20;
    return canvas.toDataURL('image/png').split(',')[1];
  });
  const reports = [];
  for (const title of ['Municipal lifecycle test hazard', 'Unverified deletion test hazard']) {
    const response = await request.post(`${origin}/api/reports`, {
      headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
      multipart: { category_id: 'tree', title, description: 'Synthetic hazard used only in isolated browser verification.', address: 'Test Road', barangay: 'Anambongan', severity: 'high', selected_latitude: '14.5995', selected_longitude: '120.9842', location_source: 'manual', location_confirmed: '1', 'photos[0]': { name: 'hazard.png', mimeType: 'image/png', buffer: Buffer.from(image, 'base64') } },
    });
    expect(response.status()).toBe(201);
    reports.push((await response.json()).data);
  }
  await signIn(page, 'admin@example.test', process.env.E2E_ADMIN_PASSWORD!);
  await page.getByRole('button', { name: /^Review reports(?: \d+)?$/ }).click();
  await page.getByRole('button', { name: /Municipal lifecycle test hazard/ }).click();
  const detail = page.getByRole('dialog', { name: 'Hazard report', exact: true });
  await expect(detail.getByRole('heading', { name: 'Report timeline' })).toBeVisible();
  await detail.getByLabel('Verified latitude', { exact: true }).fill('14.6001');
  await detail.getByLabel('Verified longitude', { exact: true }).fill('120.9843');
  for (const status of ['under_verification', 'verified', 'assigned', 'responding', 'resolved', 'closed']) {
    if (status === 'verified') {
      await reviewIncidentImages(page);
    await detail.getByLabel('Verification method', { exact: true }).selectOption('responder_confirmation');
      await detail.getByLabel('Internal verification notes', { exact: true }).fill('A municipal responder independently checked this fictional incident.');
      await detail.getByRole('checkbox', { name: 'I verified that this incident actually happened.' }).check();
      await detail.getByRole('button', { name: 'Verify and accept incident', exact: true }).click();
      await page.getByRole('dialog', { name: 'Accept this verified incident?' }).getByRole('button', { name: 'Accept incident', exact: true }).click();
      await expect(detail.locator('.detail-badges .status')).toHaveClass('status verified');
      continue;
    }
    if (status === 'resolved') {
      await detail.getByLabel('Resolution evidence', { exact: true }).setInputFiles({ name: 'resolution.png', mimeType: 'image/png', buffer: Buffer.from(image, 'base64') });
      await detail.getByRole('button', { name: 'Upload resolution photos', exact: true }).click();
      await expect(detail.getByText('Resolution photos saved privately.', { exact: true })).toBeVisible();
      await expect(detail.getByRole('img', { name: 'Resolution evidence photo 1' })).toBeVisible();
    }
    await detail.getByRole('combobox', { name: 'Status', exact: true }).selectOption(status);
    await detail.getByLabel('Assigned responder or team').fill('Isolated engineering team');
    await detail.getByLabel('Response update').fill(`Municipal test transition to ${status}.`);
    await detail.getByRole('button', { name: 'Save response update', exact: true }).click();
    await page.getByRole('dialog', { name: 'Save municipal response?' }).getByRole('button', { name: 'Save response', exact: true }).click();
    await expect(page.getByRole('dialog', { name: 'Save municipal response?' })).toHaveCount(0);
    await expect(detail.locator('.detail-badges .status')).toHaveClass(`status ${status}`);
  }
  await expect(detail.getByRole('heading', { name: 'Municipal response' })).toHaveCount(0);
  await detail.getByRole('button', { name: 'Close dialog' }).click();
  await page.getByRole('button', { name: 'Sign out', exact: true }).click();
  await page.getByRole('dialog', { name: 'Sign out?' }).getByRole('button', { name: 'Sign out', exact: true }).click();
  await expect(page.locator('.sidebar-user')).toContainText('Community guest');
  await signIn(page, email, password);
  await page.getByRole('button', { name: 'Open notifications', exact: true }).click();
  await expect(page.locator('.notification-row')).toHaveCount(6);
  await page.getByRole('button', { name: 'Mark all read' }).click();
  await expect(page.locator('.notification-row.unread')).toHaveCount(0);
  await page.locator('.notification-row').first().click();
  await expect(detail.locator('.detail-badges .status')).toHaveText('Closed');
  await expect(detail.locator('.location-evidence')).toContainText('14.600100, 120.984300');
  await expect(detail.getByRole('img', { name: 'Resolution evidence photo 1' })).toBeVisible();
  await detail.getByRole('button', { name: 'Close dialog' }).click();
  await page.getByRole('button', { name: 'My profile', exact: true }).click();
  await page.getByLabel('Name', { exact: true }).fill('Updated Workflow Citizen');
  await page.getByRole('button', { name: 'Save profile', exact: true }).click();
  await page.getByRole('dialog', { name: 'Save profile changes?' }).getByRole('button', { name: 'Save profile', exact: true }).click();
  await expect(page.getByText('Profile updated.', { exact: true })).toBeVisible();
  await page.reload();
  await expect(page.locator('.sidebar-user')).toContainText('Updated Workflow Citizen');
  await page.getByRole('button', { name: 'My reports', exact: true }).click();
  await page.getByRole('button', { name: /Unverified deletion test hazard/ }).click();
  await expect(detail.getByRole('button', { name: 'Delete unverified report' })).toBeVisible();
  await detail.getByRole('button', { name: 'Delete unverified report' }).click();
  await page.getByRole('dialog', { name: 'Delete this report?' }).getByRole('button', { name: 'Delete report', exact: true }).click();
  await expect(detail).toHaveCount(0);
  await expect(page.locator('tbody')).not.toContainText('Unverified deletion test hazard');
  const deleted = await request.get(`${origin}/api/reports/${reports[1].id}`, { headers: { Accept: 'application/json', Authorization: `Bearer ${token}` } });
  expect(deleted.status()).toBe(404);
  expect(errors).toEqual([]);
});
