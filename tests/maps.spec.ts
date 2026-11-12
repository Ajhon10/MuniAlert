import { test, expect, continueReport } from './fixtures';

// Deterministic background styles; actual bundled Basista/Pangasinan geometry
// still passes through the real MapLibre worker and WebGL renderer.
test.beforeEach(async ({ page }) => {
  await page.route('https://tiles.openfreemap.org/styles/*', route => route.fulfill({ json: { version: 8, sources: {}, layers: [{ id: 'test-background', type: 'background', paint: { 'background-color': route.request().url().endsWith('/dark') ? '#152b2b' : '#eef2eb' } }] } }));
  await page.route('https://tile.openstreetmap.org/**', route => route.fulfill({ contentType: 'image/png', body: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', 'base64') }));
});

test('map filters isolate fire and accidents, stay synchronized, and survive expanding the map', async ({ page }) => {
  await page.goto('/?demo=1');
  await expect(page.locator('.map-pin')).toHaveCount(7);
  const category = page.getByRole('combobox', { name: 'Map hazard category', exact: true });
  await category.selectOption('fire');
  await expect(page.locator('.map-pin')).toHaveCount(1);
  await expect(page.locator('.map-pin')).toHaveAttribute('aria-label', /^Fire:/);
  await expect(page.getByRole('combobox', { name: 'Filter by category', exact: true })).toHaveValue('fire');
  await page.getByRole('button', { name: 'Expand map', exact: true }).click();
  await expect(category).toHaveValue('fire');
  await expect(page.locator('.map-pin')).toHaveCount(1);
  await category.selectOption('accident');
  await expect(page.locator('.map-pin')).toHaveCount(1);
  await expect(page.locator('.map-pin')).toHaveAttribute('aria-label', /^Road accident:/);
  await category.selectOption('vegetation');
  await expect(page.locator('.map-pin')).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Fit reports', exact: true })).toBeDisabled();
  await page.getByRole('button', { name: 'Show all hazard types', exact: true }).click();
  await expect(page.locator('.map-pin')).toHaveCount(7);
});

test('boundary switches draw real local geometry and style changes retain overlays, markers and preferences', async ({ page, request }) => {
  const errors: string[] = []; page.on('pageerror', error => errors.push(error.message));
  const response = await request.get('/boundaries/basista-barangays.geojson');
  const collection = await response.json();
  expect(collection.features).toHaveLength(13);
  expect(collection.features.map((feature: any) => feature.properties.name)).toContain('Osmeña Sr.');
  const packaged = await request.get(`http://127.0.0.1:${process.env.HAZARD_API_PORT || '18000'}/app/boundaries/basista-barangays.geojson`);
  expect((await packaged.json()).features).toHaveLength(13);
  await page.goto('/?demo=1&view=map');
  await expect(page.locator('.map-pin')).toHaveCount(7);
  await page.getByRole('button', { name: 'Basista area', exact: true }).click();
  await page.locator('.boundary-options summary').click();
  const barangays = page.getByRole('checkbox', { name: 'Barangay boundaries', exact: true });
  const cities = page.getByRole('checkbox', { name: 'City and municipality boundaries', exact: true });
  await barangays.uncheck(); await cities.uncheck();
  const canvas = page.locator('.hazard-map canvas');
  await expect(canvas).toBeVisible();
  await page.waitForTimeout(400); // Settle the deliberate fit animation before comparing geometry.
  const without = await canvas.screenshot();
  await barangays.check();
  await expect.poll(async () => Buffer.compare(await canvas.screenshot(), without)).not.toBe(0);
  await page.getByRole('button', { name: 'Dark map', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Dark map', exact: true })).toHaveAttribute('aria-pressed', 'true');
  await expect(page.locator('.map-pin')).toHaveCount(7);
  await expect(barangays).toBeChecked();
  await page.reload();
  await expect(page.getByRole('button', { name: 'Dark map', exact: true })).toHaveAttribute('aria-pressed', 'true');
  await page.locator('.boundary-options summary').click();
  await expect(barangays).toBeChecked(); await expect(cities).not.toBeChecked();
  await expect(page.locator('.map-pin')).toHaveCount(7);
  expect(errors).toEqual([]);
});

test('map controls reflow on small phones and handle an unavailable style with a retry', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 800 });
  await page.route('https://tiles.openfreemap.org/styles/positron', route => route.abort());
  await page.goto('/?demo=1&view=map');
  await expect(page.getByText('Some map content could not load.', { exact: false })).toBeVisible();
  await page.unroute('https://tiles.openfreemap.org/styles/positron');
  await page.getByRole('button', { name: 'Retry map', exact: true }).click();
  await expect(page.locator('.map-load-error')).toHaveCount(0);
  await expect(page.locator('.map-pin')).toHaveCount(7);
  await page.getByRole('button', { name: 'Dark map', exact: true }).click();
  await expect(page.locator('.map-load-error')).toHaveCount(0);
  await expect(page.locator('.map-pin')).toHaveCount(7);
  await page.locator('.boundary-options summary').click();
  for (const width of [320, 390, 768, 1440]) {
    await page.setViewportSize({ width, height: 800 });
    await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  }
  await page.setViewportSize({ width: 390, height: 844 });
  await page.locator('.map-panel').screenshot({ path: 'test-results/basista-map-phone.png', animations: 'disabled' });
  await page.setViewportSize({ width: 320, height: 800 });
  await page.locator('.map-controls').evaluate(element => {
    const nodes = [...element.querySelectorAll<HTMLElement>('legend,label,button,summary,p,span,select')];
    const sizes = nodes.map(node => parseFloat(getComputedStyle(node).fontSize));
    nodes.forEach((node, index) => { node.style.fontSize = `${sizes[index]! * 2}px`; });
  });
  await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
});

test('changing the location map style preserves the selected and confirmed incident pin', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/?demo=1');
  await page.getByRole('button', { name: 'Create hazard report', exact: true }).click(); await continueReport(page);
  const form = page.getByRole('dialog', { name: 'Report a hazard', exact: true });
  await form.getByRole('button', { name: 'Choose location on map' }).click();
  await form.getByLabel('Selected latitude', { exact: true }).fill('15.86');
  await form.getByLabel('Selected longitude', { exact: true }).fill('120.4');
  await form.getByRole('button', { name: 'Confirm location', exact: true }).click();
  await form.getByRole('combobox', { name: 'Location map style', exact: true }).selectOption('dark');
  await expect(form.getByLabel('Selected latitude', { exact: true })).toHaveValue('15.86');
  await expect(form.getByLabel('Selected longitude', { exact: true })).toHaveValue('120.4');
  await expect(form.getByRole('button', { name: 'Location confirmed', exact: true })).toBeVisible();
  await expect(form.getByRole('combobox', { name: 'Barangay', exact: true }).locator('option')).toHaveCount(14);
});
