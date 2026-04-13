import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';

function runHelper(args) {
  return execFileSync('php', ['scripts/playwright_recipient_request_flow.php', ...args], {
    cwd: process.cwd(),
    encoding: 'utf8',
  }).trim();
}

function seedRecipientRequestFlow() {
  const uniqueSuffix = `${Date.now()}${Math.floor(Math.random() * 1000)}`;
  return JSON.parse(runHelper(['seed', uniqueSuffix]));
}

function fetchCreatedRequest(itemId) {
  const output = runHelper(['fetch', String(itemId)]);
  return output ? JSON.parse(output) : null;
}

test.describe('recipient request flow', () => {
  test.setTimeout(60000);

  test('recipient can request an item from the provider menu', async ({ page }) => {
    const seeded = seedRecipientRequestFlow();

    await page.goto('/login');
    await page.locator('#email').fill(seeded.recipientEmail);
    await page.locator('#password').fill(seeded.password);

    await Promise.all([
      page.waitForURL(/\/recipient\/dashboard$/),
      page.locator('form[action$="/login"] button[type="submit"]').click(),
    ]);

    await page.goto(`/recipient/providers/${seeded.providerId}`);
    await expect(page.locator('h1')).toContainText(seeded.businessName);
    await expect(page.getByText(seeded.itemName)).toBeVisible();

    await page.locator(`button[onclick^="openItemModal(${seeded.itemId},"]`).click();
    await expect(page.locator('#item-modal')).toBeVisible();
    await page.locator('button[onclick="addToCart()"]').click();

    await expect(page.locator('#submit-btn')).toBeEnabled();

    await page.locator('#submit-btn').click();
    // Controller redirects to recipient.requests.show after successful store
    await page.waitForURL(/\/recipient\/requests\/\d+$/);
    await expect(page.locator('main')).toContainText(seeded.itemName);

    const createdRequest = fetchCreatedRequest(seeded.itemId);
    expect(createdRequest).not.toBeNull();
    expect(createdRequest.status).toBe('REQUESTED');
    expect(createdRequest.reserved_amount).toBe(45);
  });
});
