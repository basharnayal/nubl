import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig, devices } from '@playwright/test';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const testDir = path.join(__dirname, '../../tests/e2e');

export default defineConfig({
  testDir,
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
