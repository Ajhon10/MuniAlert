import { defineConfig, devices } from '@playwright/test';
export default defineConfig({
  testDir: './tests', timeout: 60000, expect: { timeout: 15000 }, fullyParallel: false, workers: 1,
  use: { baseURL: `http://127.0.0.1:${process.env.HAZARD_WEB_PORT || 15173}`, trace: 'retain-on-failure', screenshot: 'only-on-failure' },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
