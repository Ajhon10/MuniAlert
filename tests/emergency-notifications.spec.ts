import { test, expect, finishAdminMfa, navigateWorkspace } from './fixtures';
import type { Page, APIRequestContext } from '@playwright/test';
async function browserInbox(page: Page) {
  const result = await page.evaluate(async () => { const response = await fetch('/api/notifications', { headers: { Accept: 'application/json' } }); return { status: response.status, data: await response.json() }; });
  expect(result.status).toBe(200); expect(Array.isArray(result.data)).toBe(true); return result.data;
}
async function adminLogin(page: Page) {
  await page.goto('/'); const initialInbox = page.waitForResponse(response => new URL(response.url()).pathname === '/api/notifications'); await page.getByRole('button', { name: 'Account', exact: true }).click();
  const auth = page.getByRole('dialog', { name: 'Welcome to your community', exact: true });
  await auth.getByLabel('Email address', { exact: true }).fill('admin@example.test');
  await auth.getByLabel('Password', { exact: true }).fill(process.env.E2E_ADMIN_PASSWORD!);
  await auth.getByRole('button', { name: 'Sign in', exact: true }).click(); await finishAdminMfa(page); await initialInbox;
  await expect(page.getByLabel('Connected to municipality', { exact: true })).toBeVisible();
}
async function postReport(request: APIRequestContext, page: Page, origin: string, token: string, category: string, urgent = true) {
  const bytes = await page.evaluate(() => { const canvas = document.createElement('canvas'); canvas.width = canvas.height = 20; return canvas.toDataURL('image/png').split(',')[1]!; });
  const response = await request.post(`${origin}/api/reports`, { headers: { Authorization: `Bearer ${token}` }, multipart: { category_id: category, title: `Synthetic ${category} assistance request`, description: 'Synthetic report for testing municipal attention tools.', address: 'Synthetic Road', barangay: 'Anambongan', severity: 'high', selected_latitude: '15.864', selected_longitude: '120.399', location_source: 'manual', location_confirmed: '1', assistance_needed: urgent ? '1' : '0', 'photos[0]': { name: 'synthetic.png', mimeType: 'image/png', buffer: Buffer.from(bytes, 'base64') } } });
  expect(response.status()).toBe(201); return (await response.json()).data;
}

test('emergency-first hotlines search and distance sorting remain usable at 200 percent and can continue without a call', async ({ page, context }) => {
  await context.grantPermissions(['geolocation']); await context.setGeolocation({ latitude: 15.864, longitude: 120.399, accuracy: 10 });
  await page.addInitScript(() => localStorage.setItem('munialert-reading-v1', JSON.stringify({ textSize: 200, reduceMotion: true })));
  await page.setViewportSize({ width: 375, height: 900 });
  await page.route('**/api/settings', route => route.fulfill({ json: { map: { latitude: 15.864, longitude: 120.399, zoom: 13 }, hotlines: [{ name: 'Synthetic nearby medical agency', number: '0000000001', service: 'medical', area: 'Anambongan', latitude: 15.865, longitude: 120.4 }, { name: 'Synthetic farther fire agency', number: '0000000002', service: 'fire', area: 'Other test area', latitude: 16, longitude: 120.5 }] } }));
  await page.goto('/'); await page.getByRole('button', { name: 'Create hazard report', exact: true }).click();
  await page.getByRole('dialog', { name: 'Before you report', exact: true }).getByRole('button', { name: 'Yes, immediate help is needed', exact: true }).click();
  const directory = page.getByRole('dialog', { name: 'Get immediate help', exact: true });
  await directory.getByLabel('Search hotlines', { exact: true }).fill('medical');
  await expect(directory.locator('.hotline-results li')).toHaveCount(1);
  await expect(directory.getByRole('link', { name: 'Call Synthetic nearby medical agency at 0000000001', exact: true })).toHaveAttribute('href', 'tel:0000000001');
  await directory.getByLabel('Search hotlines', { exact: true }).fill('');
  await directory.getByRole('button', { name: 'Show closest agencies', exact: true }).click();
  await expect(directory.locator('.hotline-results li').first()).toContainText('Synthetic nearby medical agency');
  await expect(directory).toContainText('No cellular load or unable to call?');
  expect(await directory.evaluate(element => element.scrollWidth <= element.clientWidth + 1)).toBe(true);
  await directory.screenshot({ path: 'test-results/emergency-hotlines-phone-200.png', animations: 'disabled' });
  await directory.getByRole('button', { name: 'Continue to report online', exact: true }).click();
  await page.getByRole('button', { name: 'Create an account', exact: true }).click();
  await page.getByLabel('Full name', { exact: true }).fill('Synthetic Emergency Reporter');
  await page.getByLabel('Email address', { exact: true }).fill(`emergency-${Date.now()}@example.test`);
  await page.getByLabel('Password', { exact: true }).fill('synthetic-emergency-reporter-password');
  await page.getByRole('button', { name: 'Create your account', exact: true }).click();
  const form = page.getByRole('dialog', { name: 'Report a hazard', exact: true });
  await expect(form.locator('.assistance-banner')).toContainText('highlighted to municipal staff');
  await form.getByRole('button', { name: 'Road accident', exact: true }).click();
  const bytes = await page.evaluate(() => { const canvas = document.createElement('canvas'); canvas.width = canvas.height = 20; return canvas.toDataURL('image/png').split(',')[1]!; });
  await form.getByLabel('Add hazard photos').setInputFiles({ name: 'synthetic-accident.png', mimeType: 'image/png', buffer: Buffer.from(bytes, 'base64') });
  await form.getByRole('button', { name: 'Choose location on map Place the pin yourself', exact: true }).click();
  await form.getByLabel('Selected latitude', { exact: true }).fill('15.864'); await form.getByLabel('Selected longitude', { exact: true }).fill('120.399');
  await form.getByRole('button', { name: 'Confirm location', exact: true }).click();
  await form.getByLabel('Barangay', { exact: true }).selectOption('Anambongan'); await form.getByLabel('Street or landmark', { exact: true }).fill('Synthetic Road');
  await form.getByLabel('Report title', { exact: true }).fill('Synthetic accident needing assistance'); await form.getByLabel('Description', { exact: true }).fill('Synthetic incident submitted to check the emergency assistance flag.');
  const nearby = page.waitForResponse(response => response.url().includes('/api/reports/nearby?'));
  await form.getByRole('button', { name: 'Submit hazard report', exact: true }).click();
  if ((await (await nearby).json()).length) { await form.getByRole('checkbox', { name: 'This is a different hazard; submit a new report.', exact: true }).check(); await form.getByRole('button', { name: 'Submit hazard report', exact: true }).click(); }
  const submitted = page.waitForResponse(response => new URL(response.url()).pathname === '/api/reports' && response.request().method() === 'POST');
  await page.getByRole('dialog', { name: 'Submit this hazard report?', exact: true }).getByRole('button', { name: 'Submit report', exact: true }).click();
  const result = await (await submitted).json(); expect(result.data.assistance_needed).toBe(true);
  const detail = page.getByRole('dialog', { name: 'Hazard report', exact: true });
  await expect(detail.locator('.assistance-banner')).toContainText('Immediate assistance requested by the reporter');
  await expect(detail.locator('.image-review-panel')).toHaveCount(0);
});

test('admin gets distinct configured sounds for fresh hazards and a notification opens the correct private review', async ({ page, request }) => {
  await page.addInitScript(() => {
    (window as any).playedAlerts = [];
    class FakeAudioContext { state = 'suspended'; currentTime = 0; destination = {}; async resume() { this.state = 'running'; } createOscillator() { const frequency = { value: 0 }; return { frequency, type: 'sine', onended: null, connect() {}, disconnect() {}, start(time: number) { (window as any).playedAlerts.push({ frequency: frequency.value, time }); }, stop() {} }; } createGain() { return { gain: { setValueAtTime() {}, linearRampToValueAtTime() {}, exponentialRampToValueAtTime() {} }, connect() {}, disconnect() {} }; } }
    (window as any).AudioContext = FakeAudioContext;
  });
  await adminLogin(page); await page.getByRole('button', { name: 'Open settings', exact: true }).click();
  const sounds = page.locator('.admin-alert-settings');
  await sounds.getByRole('button', { name: 'Enable sound alerts', exact: true }).click();
  await sounds.getByLabel('Fire sound', { exact: true }).selectOption('urgent'); await sounds.getByRole('slider', { name: 'Alert volume', exact: true }).fill('70');
  const origin = `http://127.0.0.1:${process.env.HAZARD_API_PORT || '18000'}`;
  const registration = await request.post(`${origin}/api/auth/register`, { data: { name: 'Synthetic Sound Reporter', email: `sound-${Date.now()}@example.test`, password: 'synthetic-sound-reporter-password' } }); expect(registration.status()).toBe(201);
  const token = (await registration.json()).token;
  const fire = await postReport(request, page, origin, token, 'fire'); const tree = await postReport(request, page, origin, token, 'tree', false);
  await page.evaluate(() => document.dispatchEvent(new Event('visibilitychange')));
  await expect.poll(() => page.evaluate(() => (window as any).playedAlerts.length)).toBe(7);
  const frequencies = await page.evaluate(() => (window as any).playedAlerts.map((item: any) => item.frequency)); expect(frequencies).toContain(360); expect(frequencies).toContain(465);
  await page.getByRole('button', { name: 'Open notifications', exact: true }).click();
  const inbox = page.getByRole('dialog', { name: 'Notifications', exact: true });
  await expect(inbox.getByRole('button').filter({ hasText: fire.reference_number })).toBeVisible();
  await inbox.screenshot({ path: 'test-results/actionable-notifications.png', animations: 'disabled' });
  await expect(page.locator('.sidebar nav').getByRole('button', { name: 'Notifications', exact: true })).toHaveCount(0);
  await inbox.getByRole('button').filter({ hasText: fire.reference_number }).click();
  await expect(page.getByRole('heading', { name: 'Report review', exact: true })).toBeVisible();
  const detail = page.getByRole('dialog', { name: 'Hazard report', exact: true });
  await expect(detail).toContainText(fire.reference_number); await expect(detail.locator('.responder-contacts')).toHaveAttribute('open', '');
  await expect(detail.getByRole('button', { name: 'Verify and accept incident', exact: true })).toBeDisabled();
  const notices = await browserInbox(page);
  expect(notices.find((notice: any) => notice.data.report_id === fire.id).read_at).not.toBeNull(); expect(notices.find((notice: any) => notice.data.report_id === tree.id).read_at).toBeNull();
  expect(await page.evaluate(() => (window as any).playedAlerts.length)).toBe(7);
  await detail.locator('.assistance-banner').scrollIntoViewIfNeeded();
  await detail.screenshot({ path: 'test-results/admin-emergency-image-review.png', animations: 'disabled' });
  await detail.locator('.image-review-panel').screenshot({ path: 'test-results/private-image-review.png', animations: 'disabled' });
  await detail.getByRole('button', { name: 'Close dialog', exact: true }).click(); await navigateWorkspace(page, 'Settings');
  await sounds.getByRole('button', { name: 'Mute alerts', exact: true }).click();
  await postReport(request, page, origin, token, 'accident'); await page.evaluate(() => document.dispatchEvent(new Event('visibilitychange')));
  await expect.poll(async () => (await browserInbox(page)).some((notice: any) => notice.data.category_id === 'accident')).toBe(true);
  expect(await page.evaluate(() => (window as any).playedAlerts.length)).toBe(7);
});

test('floating guide stays above the phone dock and returns keyboard focus after the highlighted tour', async ({ page }) => {
  await page.addInitScript(() => localStorage.setItem('munialert-reading-v1', JSON.stringify({ textSize: 200, reduceMotion: true })));
  await page.setViewportSize({ width: 375, height: 900 }); await page.goto('/?demo=1&view=settings');
  const help = page.getByRole('button', { name: 'Start guided tour', exact: true });
  await expect(help).toBeVisible(); const box = (await help.boundingBox())!; const dock = (await page.locator('.mobile-dock').boundingBox())!;
  expect(box.height).toBeGreaterThanOrEqual(48); expect(box.width).toBeGreaterThanOrEqual(48); expect(box.y + box.height).toBeLessThanOrEqual(dock.y - 8);
  for (const button of await page.locator('.mobile-dock > button').all()) {
    expect(await button.evaluate(element => { const label = element.querySelector('span:last-child'); if (!label) return true; const range = document.createRange(); range.selectNodeContents(label); return range.getBoundingClientRect().width <= element.getBoundingClientRect().width + .5; })).toBe(true);
  }
  await page.screenshot({ path: 'test-results/floating-guide-phone-200.png', animations: 'disabled' });
  await help.click(); const tour = page.getByRole('dialog', { name: 'Feature tour', exact: true }); await expect(tour).toBeVisible();
  await tour.press('Escape'); await expect(tour).toHaveCount(0); await expect(help).toBeFocused();
});
