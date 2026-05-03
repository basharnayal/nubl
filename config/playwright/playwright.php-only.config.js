import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig, devices } from '@playwright/test';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const testDir = path.join(__dirname, '../../tests/e2e');

export default defineConfig({
  testDir,
  fullyParallel: false,
  workers: 1,
  reporter: 'html',
  use: {
    baseURL: 'http://127.0.0.1:8001',
    trace: 'on-first-retry',
  },
  webServer: {
    command: 'php artisan serve --host=127.0.0.1 --port=8001',
    // cwd must point to the project root so PHP can find the `artisan` file.
    cwd: path.resolve(__dirname, '../..'),
    url: 'http://127.0.0.1:8001',
    env: {
      ...process.env,
      PHONE_VERIFICATION_ENABLED: 'false',
      EMAIL_VERIFICATION_ENABLED: 'false',
        // E2E runs against http://127.0.0.1:8001, so production cookie
        // restrictions in .env (HTTPS-only, nublhope.com domain) would prevent
        // the browser from storing the session cookie. Override for tests.
        APP_URL: 'http://127.0.0.1:8001',
        SESSION_SECURE_COOKIE: 'false',
        SESSION_DOMAIN: '',
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
