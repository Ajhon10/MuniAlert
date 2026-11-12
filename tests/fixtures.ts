import { createHmac } from 'node:crypto';
import type { Page, APIRequestContext } from '@playwright/test';
import { test as base, expect } from '@playwright/test';
import { spawnSync } from 'node:child_process';
import { resolve } from 'node:path';
import { php, phpEnv } from '../scripts/php-runtime.mjs';

// Cases represent independent users/scenarios but share one localhost IP. Reset
// ONLY the isolated test cache between cases so their rate buckets do not add up
// into an artificial burst. Limits remain enforced throughout every test case.
const root = resolve(__dirname, '..');
export const test = base.extend<{ isolatedCache: void }>({
  isolatedCache: [async ({}, use) => {
    const expected = process.env.HAZARD_E2E_MYSQL === '1' ? 'municipal_hazards_e2e' : resolve(root, '.cache/e2e.sqlite');
    if (process.env.APP_ENV !== 'testing' || process.env.DB_DATABASE !== expected) throw Error('Refusing to clear cache outside the isolated E2E database.');
    const result = spawnSync(php, ['artisan', 'cache:clear', 'database', '--no-ansi'], {
      cwd: resolve(root, 'hazard-server'), env: phpEnv(), encoding: 'utf8', windowsHide: true,
    });
    if (result.status !== 0) throw Error(`Isolated test cache reset failed: ${result.stderr || result.stdout}`);
    const security = spawnSync(php, ['artisan', 'db:seed', '--class=BrowserSecuritySeeder', '--force', '--no-ansi'], {
      cwd: resolve(root, 'hazard-server'), env: phpEnv(), encoding: 'utf8', windowsHide: true,
    });
    if (security.status !== 0) throw Error(`Isolated MFA fixture failed: ${security.stderr || security.stdout}`);
    await use();
  }, { auto: true }],
});
export { expect };

export async function continueReport(page: Page, immediate = false) {
  const triage = page.getByRole('dialog', { name: 'Before you report', exact: true });
  if (immediate) {
    await triage.getByRole('button', { name: 'Yes, immediate help is needed', exact: true }).click();
    await page.getByRole('button', { name: 'Continue to report online', exact: true }).click();
  } else await triage.getByRole('button', { name: 'No, continue to the report', exact: true }).click();
}

export async function reviewIncidentImages(page: Page) {
  const detail = page.getByRole('dialog', { name: 'Hazard report', exact: true });
  const forms = detail.getByRole('form', { name: /^Incident photo \d+ review$/ });
  await expect(forms.first()).toBeVisible();
  for (const form of await forms.all()) {
    await form.getByLabel('Image review decision', { exact: true }).selectOption('accepted_evidence');
    await form.getByLabel('Image review notes', { exact: true }).fill('Synthetic photo inspected and checked against the independent responder record.');
    await form.getByRole('checkbox', { name: 'I inspected this image and reviewed the available assessment.', exact: true }).check();
    const saved = page.waitForResponse(response => /\/photos\/[^/]+\/review$/.test(new URL(response.url()).pathname) && response.request().method() === 'POST');
    await form.getByRole('button', { name: 'Save image review', exact: true }).click();
    expect((await saved).ok()).toBe(true);
    await expect(form.getByText(/Last decision: accepted evidence/)).toBeVisible();
  }
}

export async function reviewIncidentImagesApi(request: APIRequestContext, origin: string, token: string, id: string) {
  const headers = { Authorization: `Bearer ${token}` };
  const response = await request.get(`${origin}/api/reports/${id}`, { headers }); expect(response.ok()).toBe(true);
  for (const photo of (await response.json()).data.photos.filter((item: any) => item.photo_type !== 'resolution')) {
    const reviewed = await request.post(`${origin}/api/admin/reports/${id}/photos/${photo.id}/review`, { headers, data: { assessment_version: photo.authenticity.version, verdict: 'accepted_evidence', review_note: 'Synthetic image inspected and checked with independent municipal information.', confirmed_reviewed: true } });
    expect(reviewed.ok()).toBe(true);
  }
}

export async function navigateWorkspace(page: Page, name: string) {
  const target = page.locator('.sidebar nav').getByRole('button', { name, exact: true });
  if (!await target.isVisible()) await page.getByRole('button', { name: 'More navigation', exact: true }).click();
  await target.click();
}
export async function openReading(page: Page) {
  await page.getByRole('button', { name: 'Open settings', exact: true }).click();
  await page.getByRole('button', { name: 'Reading and accessibility settings', exact: true }).click();
}
export async function fillTwoFactor(page: Page, code: string) {
  const backup = page.getByRole('button', { name: 'Use authenticator code', exact: true });
  if (await backup.isVisible()) await backup.click();
  const fields = page.locator('dialog[open]').last().locator('.two-factor-digit');
  await expect(fields).toHaveCount(6);
  await fields.first().fill(code);
  for (let i = 0; i < 6; i++) await expect(fields.nth(i)).toHaveValue(code[i]!);
}

// Test-only TOTP generation for the synthetic administrator. Runtime verification
// uses the maintained PHP library, never this browser-test helper.
export function adminOtp() {
  const secret = process.env.E2E_ADMIN_MFA_SECRET!;
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  const bits = [...secret].map(c => alphabet.indexOf(c).toString(2).padStart(5, '0')).join('');
  const key = Buffer.from(bits.match(/.{8}/g)!.map(byte => parseInt(byte, 2)));
  const counter = Buffer.alloc(8); counter.writeBigUInt64BE(BigInt(Math.floor(Date.now() / 30000)));
  const digest = createHmac('sha1', key).update(counter).digest(); const offset = digest[digest.length - 1]! & 15;
  return ((digest.readUInt32BE(offset) & 0x7fffffff) % 1000000).toString().padStart(6, '0');
}
export async function finishAdminMfa(page: Page) {
  await fillTwoFactor(page, adminOtp());
  await page.getByRole('button', { name: 'Verify and sign in', exact: true }).click();
  await expect(page.getByRole('dialog', { name: 'Welcome to your community' })).toHaveCount(0);
}
export async function adminApiToken(request: APIRequestContext, origin: string) {
  const pending = await request.post(`${origin}/api/auth/login`, { data: { email: 'admin@example.test', password: process.env.E2E_ADMIN_PASSWORD }, headers: { Accept: 'application/json' } });
  expect(pending.ok()).toBe(true); const challenge = await pending.json();
  expect(challenge.token).toBeUndefined(); expect(challenge.mfa_required).toBe(true);
  const result = await request.post(`${origin}/api/auth/mfa/verify`, { data: { challenge_token: challenge.challenge_token, code: adminOtp() }, headers: { Accept: 'application/json' } });
  expect(result.ok()).toBe(true); return (await result.json()).token as string;
}
