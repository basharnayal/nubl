import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';

const VALID_BASE64_IMAGE =
  'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

function runHelper(args) {
  return execFileSync('php', ['scripts/playwright_auth_flows.php', ...args], {
    cwd: process.cwd(),
    encoding: 'utf8',
  }).trim();
}

function ensureRoles() {
  runHelper(['ensure-roles']);
}

function seedLoginUsers() {
  const uniqueSuffix = `${Date.now()}${Math.floor(Math.random() * 1000)}`;
  return JSON.parse(runHelper(['seed-login-users', uniqueSuffix]));
}

function fetchUserByEmail(email) {
  const output = runHelper(['user-by-email', email]);
  return output ? JSON.parse(output) : null;
}

function pdfUpload(name) {
  return {
    name,
    mimeType: 'application/pdf',
    buffer: Buffer.from('%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n'),
  };
}

async function setCheckboxChecked(locator) {
  await locator.evaluate(input => {
    input.checked = true;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });
}

async function openInEnglish(page, path) {
  await page.goto(path);
  await page.goto('/locale/en');
  await expect(page.locator('html')).toHaveAttribute('lang', /en/);
}

async function loginByEmail(page, email, password) {
  await openInEnglish(page, '/login');
  await page.locator('#email').fill(email);
  await page.locator('#password').fill(password);

  await Promise.all([
    page.waitForNavigation(),
    page.locator('form[action$="/login"] button[type="submit"]').click(),
  ]);
}

test.describe('auth registration and email login coverage', () => {
  test.setTimeout(120000);

  test('donor can register from the public form and lands on the donor dashboard', async ({ page }) => {
    ensureRoles();

    const uniqueSuffix = `${Date.now()}${Math.floor(Math.random() * 1000)}`;
    const email = `pw-register-donor-${uniqueSuffix}@example.com`;
    const phone = `05${uniqueSuffix.slice(-8).padStart(8, '4')}`;

    await openInEnglish(page, '/register');
    await page.locator('input[name="membership_type"][value="donor"]').check({ force: true });

    await page.locator('#name').fill('Playwright Registered Donor');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill('password123');
    await page.locator('#phone_number').fill(phone);

    await Promise.all([
      page.waitForURL(/\/donor\/dashboard$/),
      page.getByRole('button', { name: 'Register' }).click(),
    ]);

    const user = fetchUserByEmail(email);
    expect(user?.membership_type).toBe('donor');
    expect(user?.status).toBe('active');
    expect(user?.phone_number).toBe(`966${phone.slice(1)}`);
    expect(user?.hasRecipientProfile).toBe(false);
    expect(user?.hasProviderProfile).toBe(false);

    await expect(page.locator('main')).toContainText('Welcome, Playwright Registered Donor');
  });

  test('recipient can register from the public form and reaches approval pending with saved KYC data', async ({ page }) => {
    ensureRoles();

    const uniqueSuffix = `${Date.now()}${Math.floor(Math.random() * 1000)}`;
    const email = `pw-register-recipient-${uniqueSuffix}@example.com`;
    const phone = `05${uniqueSuffix.slice(-8).padStart(8, '5')}`;

    await openInEnglish(page, '/register');
    await page.locator('input[name="membership_type"][value="recipient"]').check({ force: true });

    await page.locator('#name').fill('Playwright Registered Recipient');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill('password123');
    await page.locator('#recipient_phone_number').fill(phone);
    await page.locator('#nationality').selectOption('Saudi Arabia');
    await page.locator('#id_type').selectOption('national_id');
    await page.locator('#short_address').fill('Playwright short address');
    await page.locator('input[name="id_photo_base64"]').evaluate((input, value) => {
      input.value = value;
    }, VALID_BASE64_IMAGE);
    await page.locator('#income_band').selectOption('1000-1500');
    await page.locator('#household_size').fill('4');
    await page.locator('#marital_status').selectOption('married');
    await page.locator('input[name="is_student"][value="0"]').check({ force: true });
    await page.locator('input[name="address_confirmation_base64"]').evaluate((input, value) => {
      input.value = value;
    }, VALID_BASE64_IMAGE);

    await Promise.all([
      page.waitForURL(/\/approval-pending$/),
      page.locator('form[action$="/register"]').evaluate(form => form.submit()),
    ]);

    const user = fetchUserByEmail(email);
    expect(user?.membership_type).toBe('recipient');
    expect(user?.status).toBe('pending_approval');
    expect(user?.phone_number).toBe(`966${phone.slice(1)}`);
    expect(user?.hasRecipientProfile).toBe(true);
    expect(user?.hasRecipientKycDetails).toBe(true);

    await expect(page.locator('main')).toContainText('Your account is under review');
  });

  test('provider can complete the multi-step registration flow and review the submitted application', async ({ page }) => {
    ensureRoles();

    const uniqueSuffix = `${Date.now()}${Math.floor(Math.random() * 1000)}`;
    const email = `pw-register-provider-${uniqueSuffix}@example.com`;
    const phone = `05${uniqueSuffix.slice(-8).padStart(8, '6')}`;
    const businessName = `Playwright Provider ${uniqueSuffix}`;

    await openInEnglish(page, '/register');
    await page.locator('input[name="membership_type"][value="provider"]').check({ force: true });

    await Promise.all([
      page.waitForURL(/\/register\/provider$/),
      page.getByRole('link', { name: 'Continue' }).click(),
    ]);

    await expect(page.locator('html')).toHaveAttribute('lang', /en/);

    await page.locator('#full_name_ar').fill('Playwright Provider Arabic');
    await page.locator('#full_name_en').fill('Playwright Provider');
    await page.locator('#phone_number').fill(phone);
    await page.locator('#email').fill(email);
    await page.locator('#business_name_ar').fill('Playwright Business Arabic');
    await page.locator('#business_name_en').fill(businessName);
    await page.locator('#unified_number').fill('7000001234');
    await setCheckboxChecked(page.locator('input[name="business_category[]"][value="restaurant"]'));
    await page.locator('#address_ar').fill('Playwright Arabic Address');
    await page.locator('#address_en').fill('Playwright English Address');
    await page.locator('#city').selectOption('medina');
    await page.locator('#location').fill('Playwright Medina');
    await page.getByRole('button', { name: 'Next' }).click();

    await page.locator('#daily_capacity').fill('60');
    await setCheckboxChecked(page.locator('input[name="service_type[]"][value="meal_preparation"]'));
    await setCheckboxChecked(page.locator('input[name="service_type[]"][value="delivery"]'));
    await page.locator('#estimated_preparation_order_time').selectOption('30 minutes');
    await page.locator('#adoption_support').selectOption('yes');
    await page.getByRole('button', { name: 'Next' }).click();

    await page.locator('#bank_name').fill('Playwright Bank');
    await page.locator('#iban').fill('SA0380000000608010167519');
    await page.locator('#account_holder_name').fill('Playwright Provider');
    await page.getByRole('button', { name: 'Next' }).click();

    await page.locator('#business_license').setInputFiles(pdfUpload('license.pdf'));
    await page.locator('#id_or_iqama').setInputFiles(pdfUpload('id.pdf'));
    await page.locator('#password').fill('password123');

    await Promise.all([
      page.waitForURL(/\/approval-pending$/),
      page.getByRole('button', { name: 'Submit Application' }).click(),
    ]);

    const user = fetchUserByEmail(email);
    expect(user?.membership_type).toBe('provider');
    expect(user?.status).toBe('pending_approval');
    expect(user?.phone_number).toBe(`966${phone.slice(1)}`);
    expect(user?.hasProviderProfile).toBe(true);
    expect(user?.hasProviderOperatingInfo).toBe(true);
    expect(user?.hasProviderFinancialInfo).toBe(true);
    expect(user?.hasProviderDocuments).toBe(true);

    await expect(page.locator('main')).toContainText('Your account is under review');
    await page.getByRole('link', { name: 'View my application' }).click();
    await expect(page).toHaveURL(/\/provider\/application$/);
    await expect(page.locator('main')).toContainText('Your application (view only). Awaiting admin approval.');
    await expect(page.locator('main')).toContainText(businessName);
  });

  test('donor can log in by email and password and is routed to the donor dashboard', async ({ page }) => {
    const seed = seedLoginUsers();

    await loginByEmail(page, seed.donor.email, seed.password);

    await expect(page).toHaveURL(/\/donor\/dashboard$/);
    await expect(page.locator('main')).toContainText(seed.donor.name);
  });

  test('pending recipient can log in by email and password and is routed to approval pending', async ({ page }) => {
    const seed = seedLoginUsers();

    await loginByEmail(page, seed.recipient.email, seed.password);

    await expect(page).toHaveURL(/\/approval-pending$/);
    await expect(page.locator('main')).toContainText('Your account is under review');
  });

  test('pending provider can log in by email and password and can access the application review link', async ({ page }) => {
    const seed = seedLoginUsers();

    await loginByEmail(page, seed.provider.email, seed.password);

    await expect(page).toHaveURL(/\/approval-pending$/);
    await expect(page.locator('main')).toContainText('Your account is under review');
    await expect(page.getByRole('link', { name: 'View my application' })).toBeVisible();
  });

  test('email login rejects invalid credentials without authenticating the user', async ({ page }) => {
    const seed = seedLoginUsers();

    await openInEnglish(page, '/login');
    await page.locator('#email').fill(seed.donor.email);
    await page.locator('#password').fill('wrong-password');
    await page.getByRole('button', { name: 'Log in' }).click();

    await expect(page).toHaveURL(/\/login$/);
    await expect(page.locator('[role="alert"]')).toContainText('These credentials do not match our records.');
  });
});
