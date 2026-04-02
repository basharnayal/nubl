import { test, expect } from '@playwright/test';

test('donor can register from /register and land on dashboard', async ({ page }) => {
  const uniqueSuffix = `${Date.now()}${Math.floor(Math.random() * 1000)}`;
  const phoneSuffix = uniqueSuffix.slice(-7).padStart(7, '0');

  await page.goto('/register');
  await expect(page).toHaveURL(/\/register$/);

  await page.locator('input[name="membership_type"][value="donor"]').check({ force: true });
  await expect(page.locator('#phone_number')).toBeVisible();

  await page.locator('#name').fill('Playwright Donor');
  await page.locator('#email').fill(`playwright-${uniqueSuffix}@example.com`);
  await page.locator('#password').fill('password123');
  await page.locator('#phone_number').fill(`05${phoneSuffix}`);

  await Promise.all([
    page.waitForURL(/\/dashboard$/),
    page.locator('button[type="submit"]').click(),
  ]);

  await expect(page).toHaveURL(/\/dashboard$/);
});
