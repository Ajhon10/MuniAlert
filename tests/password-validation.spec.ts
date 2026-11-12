import { test, expect, openReading, navigateWorkspace } from './fixtures';

test('backend validation returns field errors and one floating linked toast without moving modal content', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('button', { name: 'Account', exact: true }).click();
  const form = page.getByRole('dialog', { name: 'Welcome to your community', exact: true });
  const before = await form.locator('.modal-body').boundingBox();
  const response = page.waitForResponse(r => new URL(r.url()).pathname === '/auth/login' && r.request().method() === 'POST');
  await form.getByRole('button', { name: 'Sign in', exact: true }).click();
  const result = await response; expect(result.status()).toBe(422);
  const data = await result.json(); expect(data.code).toBe('validation_error'); expect(data.errors.email).toBeTruthy();
  const toast = form.locator('.toast-message.error'); const email = form.getByLabel('Email address', { exact: true });
  await expect(toast).toHaveCount(1); await expect(toast).toContainText('email');
  await expect(email).toBeFocused(); await expect(email).toHaveAttribute('aria-invalid', 'true');
  await expect(email).toHaveAttribute('aria-describedby', /toast-message-/);
  expect(await form.locator('.modal-body').boundingBox()).toEqual(before);
  expect(await form.locator('.toast-stack').evaluate(el => getComputedStyle(el).position)).toBe('fixed');
  await toast.getByRole('button', { name: 'Go to field', exact: true }).click(); await expect(email).toBeFocused();
  await email.fill('visibility-fixture@example.test'); await expect(toast).toHaveCount(0);
  await expect(email).not.toHaveAttribute('aria-invalid');
  await form.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(toast).toHaveCount(1); await expect(toast).toContainText('password');
  await expect(form.getByLabel('Password', { exact: true })).toBeFocused();
  await expect(form.locator('.form-error')).toHaveCount(0);
  await form.getByRole('button', { name: 'Close dialog', exact: true }).click();
  await expect(page.locator('.toast-message.error')).toHaveCount(0);
});

test('password visibility preserves values and autocomplete, never submits, resets between modes and fits large phone text', async ({ page }) => {
  await page.goto('/');
  await openReading(page);
  await page.getByRole('slider', { name: 'Text size', exact: true }).fill('200');
  await page.getByRole('button', { name: 'Close dialog', exact: true }).click();
  await page.setViewportSize({ width: 375, height: 900 });
  await page.getByRole('button', { name: 'Account', exact: true }).click();
  const form = page.getByRole('dialog', { name: 'Welcome to your community', exact: true });
  const password = form.getByLabel('Password', { exact: true });
  const sample = 'Synthetic visibility example'; let submissions = 0;
  await page.route('**/auth/login', route => { submissions++; return route.fulfill({ status: 422, json: { message: 'Synthetic denial.' } }); });
  await password.fill(sample);
  await form.getByRole('button', { name: 'Show password', exact: true }).click();
  await expect(password).toHaveAttribute('type', 'text');
  await expect(password).toHaveValue(sample);
  await expect(password).toHaveAttribute('autocomplete', 'current-password');
  const reserved = await password.evaluate(element => parseFloat(getComputedStyle(element).paddingRight));
  const toggleWidth = (await form.getByRole('button', { name: 'Hide password', exact: true }).boundingBox())!.width;
  expect(reserved).toBeGreaterThanOrEqual(toggleWidth + 6);
  await expect(form.getByRole('button', { name: 'Hide password', exact: true })).toHaveAttribute('aria-pressed', 'true');
  await form.getByRole('button', { name: 'Hide password', exact: true }).click();
  await expect(password).toHaveAttribute('type', 'password');
  expect(submissions).toBe(0);
  await expect.poll(() => form.evaluate(element => element.scrollWidth <= element.clientWidth + 1)).toBe(true);
  await form.getByRole('button', { name: 'Create an account', exact: true }).click();
  await expect(password).toHaveValue('');
  await expect(password).toHaveAttribute('autocomplete', 'new-password');
  await expect(password).toHaveAttribute('type', 'password');
  await password.scrollIntoViewIfNeeded();
  await form.locator('.password-field').screenshot({ path: 'test-results/password-toggle-200-phone.png', animations: 'disabled' });
});

test('profile and password recovery offer independent visibility controls for every password field', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('button', { name: 'Account', exact: true }).click();
  const login = page.getByRole('dialog', { name: 'Welcome to your community', exact: true });
  await login.getByLabel('Email address', { exact: true }).fill('citizen.two@example.test');
  await login.getByLabel('Password', { exact: true }).fill(process.env.HAZARD_DEMO_CITIZEN_TWO_PASSWORD!);
  await login.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page.locator('.sidebar-user')).toContainText('Sample Citizen Two');
  await page.getByRole('button', { name: 'My profile', exact: true }).click();
  for (const label of ['Current password', 'New password', 'Confirm new password']) {
    const input = page.getByLabel(label, { exact: true });
    await input.fill('Synthetic visibility example');
    await page.getByRole('button', { name: `Show ${label.toLowerCase()}`, exact: true }).click();
    await expect(input).toHaveAttribute('type', 'text');
    await page.getByRole('button', { name: `Hide ${label.toLowerCase()}`, exact: true }).click();
    await expect(input).toHaveAttribute('type', 'password');
    await input.fill('');
  }
  const token = '00000000-0000-0000-0000-000000000000.' + 'a'.repeat(64);
  await page.goto('/#reset=' + token);
  const reset = page.getByRole('dialog', { name: 'Welcome to your community', exact: true });
  await expect(reset.getByRole('heading', { name: 'Choose a new password', exact: true })).toBeVisible();
  for (const label of ['New password', 'Confirm new password']) {
    const input = reset.getByLabel(label, { exact: true });
    await input.fill('Synthetic visibility example');
    await reset.getByRole('button', { name: `Show ${label.toLowerCase()}`, exact: true }).click();
    await expect(input).toHaveAttribute('type', 'text');
    await reset.getByRole('button', { name: `Hide ${label.toLowerCase()}`, exact: true }).click();
    await expect(input).toHaveAttribute('type', 'password');
  }
});
