import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';

test.describe('admin dashboard functionality', () => {
  test.setTimeout(90000);

  function runHelper(args) {
    return execFileSync('php', ['scripts/playwright_admin_dashboard.php', ...args], {
      cwd: process.cwd(),
      encoding: 'utf8',
    }).trim();
  }

  function seedAdminData() {
    const suffix = `${Date.now()}${Math.floor(Math.random() * 1000)}`;
    return JSON.parse(runHelper(['seed', suffix]));
  }

  function fetchUser(userId) {
    const output = runHelper(['user', String(userId)]);
    return output ? JSON.parse(output) : null;
  }

  function fetchUserByEmail(email) {
    const output = runHelper(['user-by-email', email]);
    return output ? JSON.parse(output) : null;
  }

  function fetchRequest(requestId) {
    const output = runHelper(['request', String(requestId)]);
    return output ? JSON.parse(output) : null;
  }

  function fetchSetting(key) {
    return JSON.parse(runHelper(['setting', key]));
  }

  async function loginAsAdmin(page, seed) {
    await page.goto('/login');
    await page.locator('#email').fill(seed.adminEmail);
    await page.locator('#password').fill(seed.password);

    await Promise.all([
      page.waitForURL(/\/admin\/dashboard$/),
      page.locator('form[action$="/login"] button[type="submit"]').click(),
    ]);
  }

  test('admin can load dashboard and access main modules', async ({ page }) => {
    const seed = seedAdminData();

    await loginAsAdmin(page, seed);

    await expect(page).toHaveURL(/\/admin\/dashboard$/);
    await expect(page.locator('h1, h2').first()).toBeVisible();

    await page.goto('/admin/users/pending');
    await expect(page).toHaveURL(/\/admin\/users\/pending$/);
    await expect(page.locator('h1, h2').first()).toBeVisible();

    await page.goto('/admin/manage/users');
    await expect(page).toHaveURL(/\/admin\/manage\/users$/);
    await expect(page.locator('h1, h2').first()).toBeVisible();

    await page.goto('/admin/requests');
    await expect(page).toHaveURL(/\/admin\/requests$/);
    await expect(page.locator('h1, h2').first()).toBeVisible();

    await page.goto('/admin/settings/qr');
    await expect(page).toHaveURL(/\/admin\/settings\/qr$/);
    await expect(page.locator('h1, h2').first()).toBeVisible();

    await page.goto('/admin/finances');
    await expect(page).toHaveURL(/\/admin\/finances$/);
    await expect(page.locator('h1, h2').first()).toBeVisible();
  });

  test('admin can approve and reject pending accounts', async ({ page }) => {
    const seed = seedAdminData();

    await loginAsAdmin(page, seed);
    await page.goto('/admin/users/pending');

    const providerRow = page.locator('tr', { hasText: seed.pendingProviderEmail });
    await providerRow.locator('button').first().click();
    await providerRow.locator(`form[action$="/admin/users/${seed.pendingProviderId}/approve"] button`).click();

    await expect.poll(() => fetchUser(seed.pendingProviderId)?.status).toBe('active');

    await page.goto(`/admin/users/${seed.pendingRecipientId}/reject`);
    await page.locator('#rejection_reason').fill('Playwright rejection reason');
    await page.locator(`form[action$="/admin/users/${seed.pendingRecipientId}/reject"] button[type="submit"]`).click();

    await expect.poll(() => fetchUser(seed.pendingRecipientId)?.status).toBe('rejected');
  });

  test('admin can create, edit, deactivate, reactivate, and delete a managed user', async ({ page }) => {
    const seed = seedAdminData();
    const unique = `${Date.now()}${Math.floor(Math.random() * 1000)}`;
    const donorEmail = `pw-created-donor-${unique}@example.com`;
    const donorPhone = `05${unique.slice(-8).padStart(8, '8')}`;

    await loginAsAdmin(page, seed);
    await page.goto('/admin/manage/users/create');

    await page.locator('#membership_type').selectOption('donor');
    await page.locator('#name').fill('Playwright Created Donor');
    await page.locator('#email').fill(donorEmail);
    await page.locator('#phone_number').fill(donorPhone);
    await page.locator('#password').fill(seed.password);
    await page.locator('#password_confirmation').fill(seed.password);
    await page.locator('form[action$="/admin/manage/users"] button[type="submit"]').click();

    await expect(page).toHaveURL(/\/admin\/manage\/users$/);
    await expect.poll(() => fetchUserByEmail(donorEmail)?.email).toBe(donorEmail);

    const createdUser = fetchUserByEmail(donorEmail);
    await page.goto(`/admin/manage/users/${createdUser.id}/edit`);
    await page.locator('#name').fill('Playwright Updated Donor');
    await page.locator('form[action$="/admin/manage/users/' + createdUser.id + '"] button[type="submit"]').click();

    await expect.poll(() => fetchUser(createdUser.id)?.name).toBe('Playwright Updated Donor');

    await page.goto(`/admin/manage/users?search=${encodeURIComponent(donorEmail)}`);
    const row = page.locator('tr', { hasText: donorEmail });

    page.once('dialog', dialog => dialog.accept());
    await row.locator('button').first().click();
    await row.locator(`form[action$="/admin/manage/users/${createdUser.id}/deactivate"] button`).click();
    await expect.poll(() => fetchUser(createdUser.id)?.is_active).toBe(false);

    await page.goto(`/admin/manage/users?search=${encodeURIComponent(donorEmail)}`);
    const inactiveRow = page.locator('tr', { hasText: donorEmail });
    await inactiveRow.locator('button').first().click();
    await inactiveRow.locator(`form[action$="/admin/manage/users/${createdUser.id}/reactivate"] button`).click();
    await expect.poll(() => fetchUser(createdUser.id)?.is_active).toBe(true);

    await page.goto(`/admin/manage/users?search=${encodeURIComponent(donorEmail)}`);
    const activeRow = page.locator('tr', { hasText: donorEmail });
    page.once('dialog', dialog => dialog.accept());
    await activeRow.locator('button').first().click();
    await activeRow.locator(`form[action$="/admin/manage/users/${createdUser.id}"] button`).click();
    await expect.poll(() => fetchUser(createdUser.id)).toBeNull();
  });

  test('admin can approve and reject requests from the admin queue', async ({ page }) => {
    const seed = seedAdminData();

    await loginAsAdmin(page, seed);
    await page.goto('/admin/requests');

    const approveRow = page.locator('tr', { hasText: `#${seed.approveRequestId}` });
    await approveRow.locator('button').first().click();
    await page.locator('#approve-form button[type="submit"]').click();
    await expect.poll(() => fetchRequest(seed.approveRequestId)?.status).toBe('ADMIN_APPROVED');

    const rejectRow = page.locator('tr', { hasText: `#${seed.rejectRequestId}` });
    await rejectRow.locator('button').first().click();
    await page.locator('button[onclick="toggleRejectForm()"]').click();
    await page.locator('#reject-form select[name="rejection_reason_code"]').selectOption('Policy Violation');
    await page.locator('#reject-form textarea[name="rejection_reason_note"]').fill('Rejected by headed Playwright run');
    await page.locator('#reject-form button[type="submit"]').click();

    await expect.poll(() => fetchRequest(seed.rejectRequestId)?.status).toBe('ADMIN_REJECTED');
    await expect.poll(() => fetchRequest(seed.rejectRequestId)?.rejection_reason_code).toBe('Policy Violation');
  });

  test('admin can update QR settings and access finance pages and exports', async ({ page }) => {
    const seed = seedAdminData();

    await loginAsAdmin(page, seed);
    await page.goto('/admin/settings/qr');
    await page.locator('#ttl_minutes').fill('240');
    await page.locator('form[action$="/admin/settings/qr"] button[type="submit"]').click();
    await expect.poll(() => fetchSetting('qr.ttl_minutes').value).toBe('240');

    await page.goto('/admin/finances');
    await expect(page).toHaveURL(/\/admin\/finances$/);

    await page.goto(`/admin/finances/payments?donor_id=${seed.managedDonorId}&status=SUCCEEDED`);
    await expect(page.getByText(seed.paymentExternalId)).toBeVisible();

    const paymentsDownload = page.waitForEvent('download');
    await page.locator('a[href*="/admin/finances/payments/export"]').click();
    await paymentsDownload;

    await page.goto('/admin/finances/fund-transactions');
    await expect(page.getByRole('cell', { name: `#${seed.fundTransactionId}`, exact: true })).toBeVisible();

    const ledgerDownload = page.waitForEvent('download');
    await page.locator('a[href*="/admin/finances/fund-transactions/export"]').click();
    await ledgerDownload;

    const today = new Date().toISOString().slice(0, 10);
    await page.goto(`/admin/finances/reports?period=custom&date_from=${today}&date_to=${today}`);
    await expect(page.locator('dd').first()).toHaveText(/\d+/);
    await expect(page.locator('dd').nth(1)).toHaveText(/\d+\.\d{2}/);
  });
});
