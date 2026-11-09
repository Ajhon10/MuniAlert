import { test, expect } from './fixtures';

for (const failure of ['invalid JSON', 'incomplete statistics'] as const) {
  test(`dashboard survives ${failure} and recovers on retry`, async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    let broken = true;
    await page.route('**/api/stats', async route => {
      if (!broken) return route.continue();
      await route.fulfill({
        contentType: 'application/json',
        body: failure === 'invalid JSON' ? '<br>Deprecated: database configuration warning<br>{"total":0}' : '{"total":0}',
      });
    });
    await page.goto('/');
    await expect(page.locator('.server-error')).toContainText(failure === 'invalid JSON' ? 'invalid response' : 'incomplete dashboard data');
    await expect(page.locator('.metric-value').first()).toContainText('00');
    await expect(page.getByRole('button', { name: 'Report a hazard', exact: true })).toBeVisible();
    broken = false;
    await page.getByRole('button', { name: 'Retry', exact: true }).click();
    await expect(page.locator('.connection')).toHaveText('Connected');
    await expect(page.locator('.server-error')).toHaveCount(0);
    expect(errors).toEqual([]);
  });
}

test('failed refresh keeps the last valid dashboard snapshot', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.route('**/api/stats', route => route.fulfill({ json: { total: 12, active: 7, pending: 3, responding: 2, resolved: 5 } }));
  await page.goto('/');
  await expect(page.locator('.metric-value').first()).toContainText('07');
  await page.route('**/api/stats', route => route.fulfill({ json: {} }));
  await page.getByRole('button', { name: 'Refresh reports', exact: true }).click();
  await expect(page.locator('.server-error')).toContainText('incomplete dashboard data');
  await expect(page.locator('.metric-value').first()).toContainText('07');
  expect(errors).toEqual([]);
});
