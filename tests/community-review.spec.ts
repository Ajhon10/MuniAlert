import { test, expect, finishAdminMfa, adminApiToken, openReading, navigateWorkspace, reviewIncidentImages, reviewIncidentImagesApi } from './fixtures';
import type { APIRequestContext, Page } from '@playwright/test';
const password = 'isolated-community-review-password';
async function register(request: APIRequestContext, origin: string, name: string) {
  const email = `${name}-${Date.now()}@example.test`;
  const response = await request.post(`${origin}/api/auth/register`, { data: { name, email, password } });
  expect(response.status()).toBe(201); return { email, token: (await response.json()).token as string };
}
async function login(page: Page, email: string, secret: string) {
  await page.goto('/'); await page.getByRole('button', { name: 'Account', exact: true }).click();
  const dialog = page.getByRole('dialog', { name: 'Welcome to your community' });
  await dialog.getByLabel('Email address', { exact: true }).fill(email);
  await dialog.getByLabel('Password', { exact: true }).fill(secret);
  await dialog.getByRole('button', { name: 'Sign in', exact: true }).click();
}
async function image(page: Page) {
  return page.evaluate(() => { const canvas = document.createElement('canvas'); canvas.width = canvas.height = 30; canvas.getContext('2d')!.fillRect(0, 0, 30, 30); return canvas.toDataURL('image/png').split(',')[1]!; });
}
async function submit(request: APIRequestContext, origin: string, token: string, bytes: string, title: string) {
  const response = await request.post(`${origin}/api/reports`, { headers: { Authorization: `Bearer ${token}` }, multipart: { category_id: 'fire', title, description: 'PRIVATE synthetic eyewitness details.', address: 'PRIVATE synthetic house address', barangay: 'Anambongan', severity: 'high', selected_latitude: '15.864', selected_longitude: '120.399', location_source: 'manual', location_confirmed: '1', 'photos[0]': { name: 'private-test.png', mimeType: 'image/png', buffer: Buffer.from(bytes, 'base64') } } });
  expect(response.status()).toBe(201); return (await response.json()).data.id as string;
}

test('private submissions become accepted alerts and only an explicit admin post reaches the newsfeed', async ({ page, browser, request, baseURL }) => {
  const origin = `http://127.0.0.1:${process.env.HAZARD_API_PORT || '18000'}`;
  const owner = await register(request, origin, 'ReviewOwner'); const member = await register(request, origin, 'ReviewMember');
  await page.goto('/'); const bytes = await image(page); const privateTitle = `PRIVATE fire review ${Date.now()}`;
  const id = await submit(request, origin, owner.token, bytes, privateTitle);
  const outsider = await browser.newContext({ baseURL }); const ownerBrowser = await browser.newContext({ baseURL });
  try {
    const community = await outsider.newPage(); await login(community, member.email, password);
    await expect(community.locator('.sidebar-user')).toContainText('ReviewMember');
    await expect(community.getByRole('button', { name: 'Verified hazards', exact: true })).toHaveCount(0);
    await community.getByRole('button', { name: 'Hazard map', exact: true }).click();
    await expect(community.locator('main')).not.toContainText(privateTitle);
    expect((await request.get(`${origin}/api/reports?search=${encodeURIComponent(privateTitle)}`)).ok()).toBe(true);
    expect((await (await request.get(`${origin}/api/reports?search=${encodeURIComponent(privateTitle)}`)).json()).data).toHaveLength(0);
    await login(page, 'admin@example.test', process.env.E2E_ADMIN_PASSWORD!); await finishAdminMfa(page);
    await page.getByRole('button', { name: /^Review reports/ }).click();
    await page.getByRole('button', { name: new RegExp(privateTitle) }).click();
    const detail = page.getByRole('dialog', { name: 'Hazard report', exact: true });
    await expect(detail.getByLabel('Public title', { exact: true })).toHaveCount(0);
    await expect(detail.getByRole('button', { name: 'Verify and accept incident', exact: true })).toBeDisabled();
    await expect(detail.getByText('Not assessed — no automated result', { exact: true })).toBeVisible();
    await reviewIncidentImages(page);
    await detail.getByLabel('Verification method', { exact: true }).selectOption('responder_confirmation');
    await detail.getByLabel('Internal verification notes', { exact: true }).fill('PRIVATE fact-check: a municipal responder independently confirmed this fictional incident.');
    await detail.getByRole('checkbox', { name: 'I verified that this incident actually happened.' }).check();
    await detail.getByRole('button', { name: 'Verify and accept incident', exact: true }).click();
    await page.getByRole('dialog', { name: 'Accept this verified incident?' }).getByRole('button', { name: 'Accept incident', exact: true }).click();
    await expect(detail.getByRole('heading', { name: 'Incident fact-checked and accepted', exact: true })).toBeVisible();
    expect((await (await request.get(`${origin}/api/news?report=${id}`)).json()).data).toHaveLength(0);
    const accepted = await (await request.get(`${origin}/api/reports`)).json();
    expect(accepted.data.some((report: any) => report.id === id)).toBe(true);
    expect(JSON.stringify(accepted)).not.toContain(privateTitle);
    const headline = `Reviewed fire safety update ${Date.now()}`;
    await detail.getByLabel('Public title', { exact: true }).fill(headline);
    await detail.getByLabel('Public summary', { exact: true }).fill('The municipality confirmed a fire incident. Keep the area clear for responders.');
    await expect(detail.getByRole('checkbox', { name: "Include the submitter's display name in the public post." })).toBeChecked();
    await expect(detail.getByRole('article', { name: 'Public post preview' })).toContainText('ReviewOwner');
    await detail.getByRole('checkbox', { name: /Reviewed for public sharing/ }).check();
    await detail.getByRole('button', { name: 'Publish news post', exact: true }).click();
    await page.getByRole('dialog', { name: 'Publish this news post?' }).getByRole('button', { name: 'Publish post', exact: true }).click();
    await expect(detail.getByRole('heading', { name: 'Manage community news post' })).toBeVisible();
    await community.getByRole('button', { name: 'Verified news', exact: true }).click();
    const article = community.locator('.news-card').filter({ has: community.getByRole('heading', { name: headline, exact: true }) });
    await expect(article).toBeVisible(); await expect(article).not.toContainText('PRIVATE');
    await expect(article.locator('.news-reporter')).toContainText('ReviewOwner');
    await expect(article.locator('.news-facts')).toContainText('Anambongan');
    await expect(article.locator('.news-facts')).toContainText('Incident time');
    await expect(article).not.toContainText(owner.email);
    await article.getByRole('button', { name: 'Facebook', exact: true }).click();
    const shareDialog = community.getByRole('dialog', { name: 'Share verified news', exact: true });
    const facebook = new URL((await shareDialog.getByRole('link', { name: 'Open Facebook', exact: true }).getAttribute('href'))!);
    const publicUrl = facebook.searchParams.get('u')!;
    expect(publicUrl).toContain(`/news/${id}`);
    await expect(shareDialog.getByLabel('Prepared share post')).toHaveValue(new RegExp(headline));
    await shareDialog.getByRole('button', { name: 'Close dialog', exact: true }).click();
    await article.getByRole('button', { name: 'WhatsApp', exact: true }).click();
    await expect(community.getByRole('dialog', { name: 'Share verified news', exact: true }).getByRole('link', { name: 'Open WhatsApp', exact: true })).toHaveAttribute('href', /^https:\/\/wa\.me\//);
    await community.getByRole('dialog', { name: 'Share verified news', exact: true }).getByRole('button', { name: 'Close dialog', exact: true }).click();
    const shared = await request.get(publicUrl); expect(shared.status()).toBe(200);
    const html = await shared.text(); expect(html).toContain('og:title'); expect(html).toContain(headline); expect(html).toContain('ReviewOwner'); expect(html).toContain('Incident details'); expect(html).not.toContain('PRIVATE'); expect(html).not.toContain(owner.email);
    for (const close of await community.getByRole('button', { name: 'Dismiss notification', exact: true }).all()) await close.click();
    await article.screenshot({ path: 'test-results/verified-news-details.png', animations: 'disabled' });
    await openReading(community);
    await community.getByRole('slider', { name: 'Text size', exact: true }).fill('200');
    await community.getByRole('button', { name: 'Close dialog', exact: true }).click();
    await navigateWorkspace(community, 'Verified news');
    await community.setViewportSize({ width: 375, height: 900 });
    await expect.poll(() => community.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await expect(article.locator('.news-reporter')).toContainText('ReviewOwner');
    await community.setViewportSize({ width: 1280, height: 900 });
    const mine = await ownerBrowser.newPage(); await login(mine, owner.email, password);
    await expect(mine.locator('.sidebar-user')).toContainText('ReviewOwner');
    await mine.getByRole('button', { name: 'My profile', exact: true }).click();
    const fire = mine.locator('.hazard-achievement-track.fire');
    await expect(fire).toContainText('1 accepted report');
    await expect(fire.locator('.hazard-levels li.earned')).toHaveCount(1);
    await expect(fire).toContainText('Firewatch Scout');
    await community.getByRole('button', { name: 'My profile', exact: true }).click();
    await expect(community.locator('.hazard-achievement-track.fire')).toContainText('0 accepted reports');
  } finally { await outsider.close(); await ownerBrowser.close(); }
});

test('My reports shows pending accepted and rejected own submissions and legacy community lists open the map', async ({ page, request }) => {
  const origin = `http://127.0.0.1:${process.env.HAZARD_API_PORT || '18000'}`;
  const owner = await register(request, origin, 'AllStatusesOwner');
  const other = await register(request, origin, 'AllStatusesOther');
  await page.goto('/'); const bytes = await image(page); const admin = await adminApiToken(request, origin);
  const headers = { Authorization: `Bearer ${admin}` };
  await submit(request, origin, owner.token, bytes, 'Owner pending incident');
  const accepted = await submit(request, origin, owner.token, bytes, 'Owner accepted incident');
  const rejected = await submit(request, origin, owner.token, bytes, 'Owner rejected incident');
  await submit(request, origin, other.token, bytes, 'Other member private incident');
  await reviewIncidentImagesApi(request, origin, admin, accepted);
  expect((await request.post(`${origin}/api/admin/reports/${accepted}/verify`, { headers, data: { confirmed_happened: true, method: 'official_record', verification_note: 'Independent synthetic incident confirmation for the status collection.', verified_latitude: 15.864, verified_longitude: 120.399 } })).ok()).toBe(true);
  expect((await request.patch(`${origin}/api/reports/${rejected}`, { headers, data: { status: 'rejected', severity: 'high', department_id: 'fire', note: 'Synthetic review found this submission was inaccurate.' } })).ok()).toBe(true);
  await login(page, owner.email, password);
  await expect(page.locator('.sidebar-user')).toContainText('AllStatusesOwner');
  await page.goto('/?view=reports');
  await expect(page).toHaveURL(/view=map/);
  await expect(page.getByRole('heading', { name: 'Community hazard map', exact: true })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Verified hazards', exact: true })).toHaveCount(0);
  await page.getByLabel('Filter by status', { exact: true }).selectOption('verified');
  await page.getByRole('button', { name: 'My reports', exact: true }).click();
  const panel = page.locator('.reports-panel');
  await expect(panel.locator('tbody tr')).toHaveCount(3);
  await expect(panel).toContainText('Owner pending incident');
  await expect(panel).toContainText('Owner accepted incident');
  await expect(panel).toContainText('Owner rejected incident');
  await expect(panel).not.toContainText('Other member private incident');
  await expect(page.getByLabel('Filter by status', { exact: true })).toHaveValue('');
  await page.getByLabel('Filter by status', { exact: true }).selectOption('rejected');
  await expect(panel.locator('tbody tr')).toHaveCount(1);
  await panel.locator('.table-report').click();
  const detail = page.getByRole('dialog', { name: 'Hazard report', exact: true });
  await expect(detail).toContainText('Synthetic review found this submission was inaccurate.');
});

test('category milestones unlock separately and stay readable with large phone text', async ({ page, request }) => {
  const origin = `http://127.0.0.1:${process.env.HAZARD_API_PORT || '18000'}`;
  const owner = await register(request, origin, 'AchievementOwner');
  await page.goto('/'); const bytes = await image(page); const admin = await adminApiToken(request, origin);
  for (let index = 0; index < 5; index++) {
    const id = await submit(request, origin, owner.token, bytes, `Synthetic separate fire achievement ${index}`);
    await reviewIncidentImagesApi(request, origin, admin, id);
    const verified = await request.post(`${origin}/api/admin/reports/${id}/verify`, { headers: { Authorization: `Bearer ${admin}` }, data: { confirmed_happened: true, method: 'official_record', verification_note: `Independent synthetic incident record for fixture ${index}.`, verified_latitude: 15.864, verified_longitude: 120.399 } });
    expect(verified.ok()).toBe(true);
  }
  await login(page, owner.email, password);
  await expect(page.locator('.sidebar-user')).toContainText('AchievementOwner');
  await page.getByRole('button', { name: 'My profile', exact: true }).click();
  const collection = page.locator('.hazard-achievements'); const fire = collection.locator('.hazard-achievement-track.fire');
  await expect(collection.locator('.hazard-achievement-track')).toHaveCount(10);
  await expect(fire.locator('.hazard-levels li.earned')).toHaveCount(2);
  await expect(fire).toContainText('Flame Sentinel');
  await expect(collection.locator('.collection-count')).toContainText('2 / 30 earned');
  for (const close of await page.getByRole('button', { name: 'Dismiss notification', exact: true }).all()) await close.click();
  await collection.screenshot({ path: 'test-results/hazard-achievement-collection.png', animations: 'disabled' });
  await openReading(page);
  await page.getByRole('slider', { name: 'Text size', exact: true }).fill('200');
  await page.getByRole('button', { name: 'Close dialog', exact: true }).click();
  await navigateWorkspace(page, 'My profile');
  for (const width of [320, 375, 844]) {
    await page.setViewportSize({ width, height: 900 });
    await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  }
  await page.setViewportSize({ width: 375, height: 900 });
  await fire.screenshot({ path: 'test-results/fire-achievements-200-phone.png', animations: 'disabled' });
});

test('expired admin access clears cached private reports before public requests finish', async ({ page, request }) => {
  const origin = `http://127.0.0.1:${process.env.HAZARD_API_PORT || '18000'}`;
  const owner = await register(request, origin, 'CachePrivacyOwner');
  await page.goto('/');
  const secret = `PRIVATE cached report ${Date.now()}`;
  await submit(request, origin, owner.token, await image(page), secret);
  await login(page, 'admin@example.test', process.env.E2E_ADMIN_PASSWORD!); await finishAdminMfa(page);
  await page.getByRole('button', { name: /^Review reports/ }).click();
  await expect(page.locator('main')).toContainText(secret);
  let release: () => void = () => {};
  const pendingPublicRead = new Promise<void>(resolve => release = resolve);
  await page.route('**/api/reports?*', async route => { await pendingPublicRead; await route.continue(); });
  await page.route('**/api/notifications', route => route.fulfill({ status: 401, json: { message: 'Synthetic credential revocation' } }));
  try {
    await page.getByRole('button', { name: 'Refresh reports', exact: true }).click();
    await expect(page.locator('.sidebar-user')).toContainText('Community guest');
    await expect(page.locator('main')).not.toContainText(secret);
    await expect(page.locator('.reports-panel')).toContainText('Loading reports');
  } finally { release(); }
});
