import { devices } from '@playwright/test';
import { test, expect, finishAdminMfa, adminApiToken, continueReport } from './fixtures';

test.use({ ...devices['Pixel 7'] });

test.describe('Android phone interview alignment', () => {
  test('report controls reflow at 320px with simulated 200% text enlargement', async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 800 });
    await page.goto('/?demo=1');
    await page.getByRole('button', { name: 'Create hazard report', exact: true }).click(); await continueReport(page);
    const form = page.getByRole('dialog', { name: 'Report a hazard', exact: true });
    await form.evaluate(element => {
      const nodes = [...element.querySelectorAll<HTMLElement>('h2,h3,legend,label,p,small,strong,button,input,textarea,select,span')];
      const sizes = nodes.map(node => parseFloat(getComputedStyle(node).fontSize));
      nodes.forEach((node, index) => { node.style.fontSize = `${sizes[index]! * 2}px`; });
    });
    await expect.poll(() => form.evaluate(element => element.scrollWidth <= element.clientWidth + 1)).toBe(true);
    const submit = form.getByRole('button', { name: 'Submit hazard report', exact: true });
    await submit.scrollIntoViewIfNeeded();
    await expect(submit).toBeInViewport();
    const bounds = await submit.boundingBox();
    expect(bounds!.x).toBeGreaterThanOrEqual(0);
    expect(bounds!.x + bounds!.width).toBeLessThanOrEqual(320);
    await expect(form.getByRole('button', { name: 'Close dialog', exact: true })).toBeInViewport();
  });


  test('report form keeps readable controls, private evidence guidance and manual GPS fallback', async ({ page }) => {
    const errors: string[] = []; page.on('pageerror', error => errors.push(error.message));
    await page.goto('/?demo=1');
    await page.getByRole('button', { name: 'Create hazard report', exact: true }).click(); await continueReport(page);
    const form = page.getByRole('dialog', { name: 'Report a hazard', exact: true });
    await expect(form.getByText('Your evidence stays private.')).toBeVisible();
    await expect(form.getByRole('button', { name: 'Road accident', exact: true })).toHaveAttribute('aria-pressed', 'false');
    await form.getByRole('button', { name: 'Road accident', exact: true }).click();
    await expect(form.getByRole('button', { name: 'Road accident', exact: true })).toHaveAttribute('aria-pressed', 'true');
    await expect(form.locator('[lang=fil]')).toHaveCount(3);
    for (const width of [412, 360, 320]) {
      await page.setViewportSize({ width, height: 820 });
      const category = form.getByRole('button', { name: 'Road accident', exact: true });
      const bounds = await category.boundingBox(); expect(bounds!.width).toBeGreaterThanOrEqual(48); expect(bounds!.height).toBeGreaterThanOrEqual(48);
      await expect.poll(() => form.evaluate(element => element.scrollWidth <= element.clientWidth + 1)).toBe(true);
      const textSize = await form.locator('.privacy-notice p').evaluate(element => parseFloat(getComputedStyle(element).fontSize));
      expect(textSize).toBeGreaterThanOrEqual(15);
    }
    await page.setViewportSize({ width: 360, height: 800 });
    await form.evaluate(element => element.scrollTo(0, 0));
    await page.screenshot({ path: 'test-results/android-report-360.png' });
    await form.getByRole('button', { name: 'Use current location' }).click();
    await expect(form.locator('.form-error')).toHaveCount(0);
    await expect(form.locator('.toast-message.error')).toContainText('Location access failed');
    await form.getByRole('button', { name: 'Choose location on map' }).click();
    await form.getByLabel('Selected latitude', { exact: true }).fill('14.5995');
    await form.getByLabel('Selected longitude', { exact: true }).fill('120.9842');
    await form.getByRole('button', { name: 'Confirm location', exact: true }).click();
    await expect(form.getByRole('button', { name: 'Location confirmed', exact: true })).toBeVisible();
    const buttonBounds = await form.getByRole('button', { name: 'Location confirmed', exact: true }).boundingBox();
    expect(buttonBounds!.height).toBeGreaterThanOrEqual(48);
    await form.getByRole('combobox', { name: 'Is anyone injured?', exact: true }).selectOption('unknown');
    await expect(form.getByLabel('People affected (optional estimate)', { exact: true })).toHaveValue('');
    await form.getByRole('button', { name: 'Close dialog', exact: true }).click();
    await page.getByRole('dialog', { name: 'Discard this report?' }).getByRole('button', { name: 'Discard report', exact: true }).click();
    await expect(form).toHaveCount(0);
    expect(errors).toEqual([]);
  });

  test('administrator can read period records and export on an Android viewport', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('button', { name: 'Account', exact: true }).click();
    const auth = page.getByRole('dialog', { name: 'Welcome to your community' });
    await auth.getByLabel('Email address').fill('admin@example.test');
    await auth.getByLabel('Password', { exact: true }).fill(process.env.E2E_ADMIN_PASSWORD!);
    await auth.getByRole('button', { name: 'Sign in', exact: true }).click();
  await finishAdminMfa(page);
    await expect(page.getByRole('heading', { name: 'Municipal dashboard', exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'More navigation', exact: true }).click();
    await page.getByRole('button', { name: 'Incident summary', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Incident records by period', exact: true })).toBeVisible();
    await expect(page.locator('.analytics-period')).not.toHaveCount(0);
    await page.getByRole('combobox', { name: 'Group records', exact: true }).selectOption('week');
    await page.getByRole('button', { name: 'Update summary', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Export summary', exact: true })).toBeEnabled();
    const download = page.waitForEvent('download');
    await page.getByRole('button', { name: 'Export summary', exact: true }).click();
    expect((await download).suggestedFilename()).toContain('munialert-week-');
    await expect(page.getByText(/Counts describe reports, not confirmed casualties/)).toBeVisible();
    await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await page.screenshot({ path: 'test-results/android-incident-summary.png', fullPage: true });
  });
});
