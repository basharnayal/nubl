import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig, devices } from '@playwright/test';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const testDir = path.join(__dirname, '../../tests/e2e');

export default defineConfig({
  testDir,
  fullyParallel: true,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',

  use: {
    // Point this to your standard local Laravel port
    baseURL: 'http://127.0.0.1:8001',
    trace: 'on-first-retry',
  },

  // Automatically start your Laravel backend and Vite frontend
  webServer: [
    {
      command: 'php artisan serve --host=127.0.0.1 --port=8001',
      // cwd must point to the project root so PHP can find the `artisan` file.
      // Without this, Playwright defaults to the config file's directory (config/playwright/)
      // and PHP fails with "Could not open input file: artisan".
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
    {
      command: 'npm run dev',
      // cwd must point to the project root so npm can find package.json.
      cwd: path.resolve(__dirname, '../..'),
      // Use `port` (TCP check), not `url` (HTTP check): Vite often returns 404 for GET `/`,
      // and Playwright treats that as "not ready" until timeout.
      port: 5173,
      reuseExistingServer: true,
      timeout: 120 * 1000,
    },
  ],

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'], reducedMotion: 'reduce' },
    },
    // Add Firefox or WebKit if desired
  ],
});
