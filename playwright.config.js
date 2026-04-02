import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
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
      url: 'http://127.0.0.1:8001',
      env: {
        ...process.env,
        PHONE_VERIFICATION_ENABLED: 'false',
        EMAIL_VERIFICATION_ENABLED: 'false',
      },
      reuseExistingServer: true,
      timeout: 120 * 1000,
    },
    {
      command: 'npm run dev',
      url: 'http://localhost:5173', // Default Vite port
      reuseExistingServer: true,
      timeout: 120 * 1000,
    }
  ],

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    // Add Firefox or WebKit if desired
  ],
});
