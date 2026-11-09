import { mkdirSync, writeFileSync, copyFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import { createHash } from 'node:crypto';

const root = resolve(import.meta.dirname, '..');
const workspace = resolve(root, '..');
if (!process.env.PLAYWRIGHT_BROWSERS_PATH && existsSync(resolve(workspace, '.cache/playwright'))) process.env.PLAYWRIGHT_BROWSERS_PATH = resolve(workspace, '.cache/playwright');
const { chromium, expect } = await import('@playwright/test');
const origin = process.env.PROMO_APP_ORIGIN || 'http://localhost:5173';
const output = resolve(root, 'public/screens'); mkdirSync(output, { recursive: true });
const browser = await chromium.launch();
const manifest = { capturedAt: new Date().toISOString(), origin, provenance: 'Actual Vue interface captured with synthetic API fixtures. No account credentials or production incident records. All API writes blocked.', files: [], errors: [] };
const reportId = '019a6311-0000-7000-8000-000000000001';
const categories = [{ id: 'accident', name: 'Road accident', department_id: 'mdrrmo' }, { id: 'tree', name: 'Fallen tree', department_id: 'engineering' }, { id: 'flood', name: 'Flooding', department_id: 'mdrrmo' }, { id: 'fire', name: 'Fire', department_id: 'fire' }];
const departments = [{ id: 'mdrrmo', name: 'Disaster Risk Reduction Office' }, { id: 'engineering', name: 'Municipal Engineering' }, { id: 'fire', name: 'Fire & Emergency Response' }];
const demoUser = { id: 70001, name: 'Demo Citizen', email: 'demo.citizen@example.test', role: 'citizen', has_password: true };
const stages = ['submitted', 'under_verification', 'verified', 'assigned', 'responding', 'resolved'];
const privateReport = { id: reportId, reference_number: 'DEMO-2026-0001', user_id: demoUser.id, reporter: { name: demoUser.name, email: demoUser.email }, title: 'Demonstration: road obstruction', description: 'Fictional example for demonstrating report tracking. No real incident or victim information.', address: 'Demonstration road near the barangay hall', barangay: 'Poblacion', selected_latitude: 15.8658, selected_longitude: 120.4005, gps_latitude: null, gps_longitude: null, gps_accuracy: null, verified_latitude: 15.8658, verified_longitude: 120.4005, location_source: 'manual', location_difference_meters: null, location_review_required: false, approximate_location: false, category: categories[0], department: departments[0], status: 'resolved', severity: 'moderate', confirmations_count: 1, photos: [], created_at: '2026-11-08T01:00:00Z', updated_at: '2026-11-08T03:30:00Z', occurred_at: '2026-11-08T00:50:00Z', injuries: 'unknown', people_affected: null, public_title: null, public_summary: null, assigned_responder: 'Demo response team', history: stages.map((status, index) => ({ id: index + 1, status, note: ['Report received for review.', 'Location and evidence being reviewed.', 'Incident location confirmed.', 'Responsible team assigned.', 'Team response documented.', 'Action taken recorded.'][index], created_at: `2026-11-08T0${index + 1}:00:00Z` })) };
const publicFire = { ...privateReport, id: '019a6311-0000-7000-8000-000000000002', reference_number: 'DEMO-2026-0002', user_id: null, reporter: undefined, title: 'Demonstration: roadside fire', description: 'Synthetic public-map example awaiting municipal review.', address: 'Approximate demonstration area', barangay: 'Anambongan', selected_latitude: 15.858, selected_longitude: 120.412, verified_latitude: null, verified_longitude: null, approximate_location: true, location_source: null, category: categories[3], department: departments[2], status: 'under_verification', severity: 'high', history: undefined };

async function fixturePage(role = 'citizen') {
  const page = await browser.newPage({ viewport: { width: 390, height: 720 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true, reducedMotion: 'reduce' });
  page.on('pageerror', error => manifest.errors.push(error.message));
  await page.route('**/api/**', async route => {
    const request = route.request(); const path = new URL(request.url()).pathname;
    if (request.method() !== 'GET') throw Error('Promotional capture refuses API writes.');
    let body;
    if (path === '/api/auth/me') body = { ...demoUser, role, name: role === 'admin' ? 'Demo Administrator' : demoUser.name };
    else if (path === '/api/catalog') body = { categories, departments };
    else if (path === '/api/stats') body = { total: 2, active: 1, pending: 1, responding: 0, resolved: 1 };
    else if (['/api/reports', '/api/reports/mine', '/api/admin/reports'].includes(path)) {
      const category = new URL(request.url()).searchParams.get('category');
      const reports = (path.endsWith('/mine') ? [privateReport] : [privateReport, publicFire]).filter(report => !category || report.category.id === category);
      body = { data: reports, meta: { total: reports.length, current_page: 1, last_page: 1 } };
    }
    else if (path === `/api/reports/${reportId}`) body = { data: privateReport };
    else if (path === '/api/settings') body = { map: { latitude: 15.865448, longitude: 120.400132, zoom: 12.5 }, hotlines: [] };
    else if (path === '/api/notifications') body = [];
    else if (path === '/api/admin/analytics') body = { from: '2026-11-01', to: '2026-11-08', group: 'week', timezone: 'Asia/Manila', total: 7, known_time: 5, date_basis: 'Demonstration records only. Incident time when supplied; submission time otherwise.', periods: [{ period: '2026-10-26', total: 2, accidents: 1, known_time: 1 }, { period: '2026-11-02', total: 5, accidents: 3, known_time: 4 }] };
    else if (path === '/api/auth/providers') body = [];
    else body = [];
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(body) });
  });
  return page;
}

async function focusForShot(locator) {
  await locator.evaluate(element => {
    const scroller = element.closest('.modal-body'); const rect = element.getBoundingClientRect();
    if (scroller) scroller.scrollTop += rect.top - scroller.getBoundingClientRect().top - 12;
    else window.scrollBy(0, rect.top - (document.querySelector('.topbar')?.getBoundingClientRect().bottom || 90) - 12);
  });
}

async function shot(page, name) {
  await page.evaluate(() => document.fonts.ready);
  for (const dialog of await page.locator('dialog.modal[open]').all()) {
    const bounds = await dialog.locator('.modal-header').boundingBox();
    if (!bounds || bounds.y < -1) throw Error(`The fixed dialog header is outside the captured viewport: ${name}`);
    await expect(dialog.locator('.modal-footer')).toBeInViewport();
  }
  const file = resolve(output, name); await page.screenshot({ path: file });
  const { readFileSync } = await import('node:fs');
  manifest.files.push({ name, viewport: '390x720', scale: 2, sha256: createHash('sha256').update(readFileSync(file)).digest('hex') });
}

try {
  const page = await fixturePage();
  await page.goto(`${origin}/?view=guide`);
  await expect(page.getByRole('heading', { name: 'Guide & user manual', exact: true })).toBeVisible();
  await shot(page, 'guide.png');
  await page.getByRole('button', { name: 'Start feature tour', exact: true }).click();
  await page.getByRole('dialog', { name: 'Feature tour' }).getByRole('button', { name: 'Next', exact: true }).click();
  await shot(page, 'tour.png');
  await page.getByRole('button', { name: 'Close feature tour', exact: true }).click();
  await page.goto(`${origin}/?view=guide`);
  await page.getByRole('button', { name: 'Read instructions: Private original evidence', exact: true }).click();
  await focusForShot(page.locator('#manual-privacy'));
  await shot(page, 'privacy.png');
  await page.goto(`${origin}/`);
  await expect(page.locator('.metric-value').first()).toContainText('01');
  await shot(page, 'home.png');
  await page.getByRole('button', { name: 'Create hazard report', exact: true }).click();
  const form = page.getByRole('dialog', { name: 'Report a hazard', exact: true });
  await form.getByRole('button', { name: 'Road accident', exact: true }).click();
  const image = await page.evaluate(() => {
    const canvas = document.createElement('canvas'); canvas.width = 600; canvas.height = 400; const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#dae8da'; ctx.fillRect(0, 0, 600, 400); ctx.fillStyle = '#799383'; ctx.fillRect(0, 220, 600, 180);
    ctx.strokeStyle = '#f2ead0'; ctx.lineWidth = 8; ctx.setLineDash([35, 25]); ctx.beginPath(); ctx.moveTo(0, 310); ctx.lineTo(600, 310); ctx.stroke();
    ctx.setLineDash([]); ctx.strokeStyle = '#806951'; ctx.lineWidth = 24; ctx.beginPath(); ctx.moveTo(200, 240); ctx.lineTo(440, 335); ctx.stroke();
    ctx.fillStyle = '#2c5843'; ctx.beginPath(); ctx.arc(195, 245, 70, 0, Math.PI * 2); ctx.fill();
    ctx.fillStyle = '#20392c'; ctx.font = '24px Arial'; ctx.fillText('Illustration for demonstration only', 100, 60);
    return canvas.toDataURL('image/png').split(',')[1];
  });
  await form.getByLabel('Add hazard photos').setInputFiles({ name: 'demo-illustration.png', mimeType: 'image/png', buffer: Buffer.from(image, 'base64') });
  await expect(form.locator('.photo-preview')).toHaveCount(1);
  await focusForShot(form.locator('fieldset').nth(1));
  await shot(page, 'report.png');
  await form.getByRole('button', { name: 'Choose location on map' }).click();
  await form.getByLabel('Selected latitude', { exact: true }).fill('15.8658');
  await form.getByLabel('Selected longitude', { exact: true }).fill('120.4005');
  await form.getByRole('button', { name: 'Confirm location', exact: true }).click();
  await focusForShot(form.locator('.location-heading'));
  await page.waitForTimeout(1500); // Allow provider tiles to paint in this actual-UI capture.
  await expect(page.locator('.map-load-error')).toHaveCount(0);
  await shot(page, 'location.png');
  await page.goto(`${origin}/?view=mine`);
  await page.getByRole('button', { name: 'Open DEMO-2026-0001', exact: true }).first().click();
  await expect(page.getByRole('heading', { name: 'Report timeline', exact: true })).toBeVisible();
  await focusForShot(page.locator('.timeline'));
  await shot(page, 'tracking.png');
  await page.goto(`${origin}/?view=map`);
  await page.getByRole('combobox', { name: 'Map hazard category', exact: true }).selectOption('fire');
  await expect(page.locator('.map-results')).toContainText('1 report on this page');
  await page.getByRole('button', { name: 'Clear map', exact: true }).click();
  await page.locator('.boundary-options > summary').click();
  await page.getByRole('checkbox', { name: 'Barangay boundaries', exact: true }).check();
  await page.locator('.boundary-options > summary').click();
  await expect(page.getByRole('button', { name: 'Fire: Demonstration: roadside fire', exact: true })).toBeVisible();
  await focusForShot(page.locator('.map-category'));
  await page.waitForTimeout(300); // Let the filtered marker/camera update finish first.
  await page.getByRole('button', { name: 'Basista area', exact: true }).click();
  await page.waitForTimeout(1800);
  await expect(page.locator('.map-load-error')).toHaveCount(0);
  await shot(page, 'map.png');
  await page.close();
  const admin = await fixturePage('admin');
  await admin.goto(`${origin}/?view=analytics`);
  await expect(admin.locator('.analytics-total')).toHaveText('7');
  await focusForShot(admin.locator('.analytics-heading'));
  await shot(admin, 'records.png');
  await admin.close();
  copyFileSync(resolve(workspace, 'hazard-client/src/config/identity.json'), resolve(root, 'public/identity.json'));
  if (manifest.errors.length) throw Error('Browser errors while capturing promotional screens.');
  console.log(`Captured ${manifest.files.length} finished-app screens with synthetic fixtures; no API writes.`);
} finally {
  writeFileSync(resolve(root, 'public/screens/manifest.json'), JSON.stringify(manifest, null, 2));
  await browser.close();
}
