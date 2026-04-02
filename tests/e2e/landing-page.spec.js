import { test, expect } from '@playwright/test';

test('landing page shows welcome content and auth links', async ({ page }) => {
  await page.goto('/');

  await expect(page).toHaveURL(/\/$/);
  await expect(page.locator('a[href$="/login"]')).toBeVisible();
  await expect(page.locator('a[href$="/register"]')).toBeVisible();
});
