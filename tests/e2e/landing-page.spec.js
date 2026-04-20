import { test, expect } from '@playwright/test';

test('landing page has a visible heading and login/register links', async ({ page }) => {
  await page.goto('/');

  await expect(page).toHaveURL(/\/$/);
  await expect(page.locator('h1')).toBeVisible();
  await expect(page.locator('a[href$="/login"]')).toBeVisible();
  await expect(page.locator('a[href*="/register"]').first()).toBeVisible();
});
