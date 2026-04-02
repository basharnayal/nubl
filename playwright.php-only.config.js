import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  reporter: 'html',
  use: {
    baseURL: 'http://127.0.0.1:8001',
    trace: 'on-first-retry',
  },
  webServer: {
    command: 'php artisan serve --host=127.0.0.1 --port=8001',
    url: 'http://127.0.0.1:8001',
    env: {
      ...process.env,
      PHONE_VERIFICATION_ENABLED: 'false',
      EMAIL_VERIFICATION_ENABLED: 'false',
    },
    reuseExistingServer: true,
    timeout: 120 * 1000,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
