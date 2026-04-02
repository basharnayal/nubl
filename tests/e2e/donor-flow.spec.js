import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';

function runHelper(args) {
  return execFileSync('php', ['scripts/playwright_donor_flow.php', ...args], {
    cwd: process.cwd(),
    encoding: 'utf8',
  }).trim();
}

function seedDonorData() {
  const uniqueSuffix = `${Date.now()}${Math.floor(Math.random() * 1000)}`;
  return JSON.parse(runHelper(['seed', uniqueSuffix]));
}

function fetchPayment(paymentId) {
  const output = runHelper(['payment', String(paymentId)]);
  return output ? JSON.parse(output) : null;
}

function fetchPaymentCount(sponsorId) {
  return JSON.parse(runHelper(['payment-count', String(sponsorId)])).count;
}

function mainContent(page) {
  return page.locator('main.main-content');
}

async function loginAs(page, email, password) {
  await page.goto('/login');
  await page.locator('#email').fill(email);
  await page.locator('#password').fill(password);

  await Promise.all([
    page.waitForURL(/\/dashboard$/),
    page.locator('form[action$="/login"] button[type="submit"]').click(),
  ]);

  await page.goto('/locale/en');
  await expect(page.locator('html')).toHaveAttribute('lang', /en/);
}

async function goToNewDonation(page) {
  await page.goto('/donor/donations/new');
  await expect(page).toHaveURL(/\/donor\/donations\/new$/);
}

async function submitDonationForm(page) {
  await Promise.all([
    page.waitForNavigation(),
    page.locator('form[action$="/donor/payments/initiate"]').evaluate(form => form.submit()),
  ]);
}

test.describe('donor flow coverage', () => {
  test.setTimeout(120000);

  test('donor dashboard shows aggregated impact without exposing beneficiary names', async ({ page }) => {
    const seed = seedDonorData();

    await loginAs(page, seed.primaryDonor.email, seed.password);
    await page.goto('/donor/dashboard');
    const main = mainContent(page);

    await expect(main).toContainText(`Welcome, ${seed.primaryDonor.name}`);
    await expect(main).toContainText('Total Donated');
    await expect(main).toContainText(`${seed.impact.totalDonated} SAR`);
    await expect(main).toContainText(`${seed.impact.donationCount} donations`);
    await expect(main).toContainText('Requests Delivered');
    await expect(main).toContainText(String(seed.impact.requestsDelivered));
    await expect(main).toContainText(`${seed.impact.amountAllocated} SAR`);
    await expect(main).toContainText(`${seed.impact.requestsFunded} requests funded`);
    await expect(main).toContainText(seed.impact.fulfilledReference);
    await expect(main).toContainText(seed.impact.redeemableReference);
    await expect(main).not.toContainText(seed.recipientName);
    await expect(main).not.toContainText(seed.providerName);

    await page.getByRole('button', { name: 'How does donation help?' }).click();
    await expect(page.getByText('Direct impact')).toBeVisible();
    await page.getByRole('button', { name: 'Got it' }).click();
    await expect(page.getByText('Direct impact')).not.toBeVisible();
  });

  test('donations index shows only succeeded donations and receipt navigation', async ({ page }) => {
    const seed = seedDonorData();

    await loginAs(page, seed.primaryDonor.email, seed.password);
    await page.goto('/donor/donations');
    const main = mainContent(page);

    await expect(page.getByRole('heading', { name: 'My Donations & Impact' })).toBeVisible();
    await expect(main).toContainText('120.50 SAR');
    await expect(main).toContainText('79.50 SAR');
    await expect(main).not.toContainText('55.25 SAR');
    await expect(main).not.toContainText('15.00 SAR');
    await expect(page.getByRole('link', { name: 'View Receipt' })).toHaveCount(2);

    await page.goto(`/donor/donations/${seed.payments.succeededOneId}/receipt`);
    await expect(page.getByRole('heading', { name: 'Donation Receipt' })).toBeVisible();
    await expect(page.locator('#receipt')).toContainText('#' + seed.payments.succeededOneId);
    await expect(page.locator('#receipt')).toContainText('120.50 SAR');
    await expect(page.locator('#receipt')).toContainText(seed.primaryDonor.name);
    await expect(page.locator('#receipt')).toContainText('allocated to 2 request(s)');
  });

  test('empty donor sees dashboard and donations empty states', async ({ page }) => {
    const seed = seedDonorData();

    await loginAs(page, seed.emptyDonor.email, seed.password);
    await page.goto('/donor/dashboard');
    const main = mainContent(page);

    await expect(main).toContainText('No donations yet');
    await expect(main).toContainText('No records yet');

    await page.goto('/donor/donations');
    await expect(mainContent(page)).toContainText('No donations yet.');
    await expect(page.getByRole('link', { name: 'Make your first donation' })).toBeVisible();
  });

  test('new donation form rejects required, minimum, and maximum edge cases without creating payments', async ({ page }) => {
    const seed = seedDonorData();
    const initialCount = fetchPaymentCount(seed.emptyDonor.id);

    await loginAs(page, seed.emptyDonor.email, seed.password);
    await goToNewDonation(page);
    const main = mainContent(page);

    await page.locator('#amount').fill('');
    await submitDonationForm(page);
    await expect(main).toContainText('The amount field is required.');
    expect(fetchPaymentCount(seed.emptyDonor.id)).toBe(initialCount);

    await page.locator('#amount').fill('0.5');
    await submitDonationForm(page);
    await expect(main).toContainText('The minimum donation amount is 1 SAR.');
    expect(fetchPaymentCount(seed.emptyDonor.id)).toBe(initialCount);

    await page.locator('#amount').fill('1000000');
    await submitDonationForm(page);
    await expect(main).toContainText('999999.99');
    expect(fetchPaymentCount(seed.emptyDonor.id)).toBe(initialCount);
  });

  test('receipt and payment detail pages enforce ownership and success-only access', async ({ page }) => {
    const seed = seedDonorData();

    await loginAs(page, seed.primaryDonor.email, seed.password);

    const failedReceiptResponse = await page.goto(`/donor/donations/${seed.payments.failedId}/receipt`);
    expect(failedReceiptResponse?.status()).toBe(404);

    const foreignReceiptResponse = await page.goto(`/donor/donations/${seed.payments.otherDonorSucceededId}/receipt`);
    expect(foreignReceiptResponse?.status()).toBe(403);

    const successResponse = await page.goto(`/donor/payments/success?payment_id=${seed.payments.succeededOneId}`);
    expect(successResponse?.status()).toBe(200);
    await expect(mainContent(page)).toContainText('Thank you! Your donation was successful.');
    await expect(mainContent(page)).toContainText('#' + seed.payments.succeededOneId);
    await expect(mainContent(page)).toContainText('120.50 SAR');

    await page.goto('/donor/payments/success');
    await expect(mainContent(page)).toContainText('Thank you! Your donation was successful.');
    await expect(mainContent(page)).not.toContainText('#' + seed.payments.succeededOneId);

    const failedResponse = await page.goto(`/donor/payments/failed?payment_id=${seed.payments.failedId}`);
    expect(failedResponse?.status()).toBe(200);
    await expect(mainContent(page)).toContainText('Payment was not completed');
    await expect(page.getByRole('link', { name: 'Try Again' })).toBeVisible();
  });

  test('another donor cannot inspect someone else payment success or failure pages', async ({ page }) => {
    const seed = seedDonorData();

    await loginAs(page, seed.otherDonor.email, seed.password);

    const foreignSuccessResponse = await page.goto(`/donor/payments/success?payment_id=${seed.payments.succeededOneId}`);
    expect(foreignSuccessResponse?.status()).toBe(403);

    const foreignFailedResponse = await page.goto(`/donor/payments/failed?payment_id=${seed.payments.failedId}`);
    expect(foreignFailedResponse?.status()).toBe(403);

    expect(fetchPayment(seed.payments.otherDonorSucceededId)?.sponsor_id).toBe(seed.otherDonor.id);
  });
});
