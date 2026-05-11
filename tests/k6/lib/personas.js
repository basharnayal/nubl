// tests/k6/lib/personas.js
// ---------------------------------------------------------------------------
// Persona flow functions. Each exported function is one complete user
// iteration suitable for k6's `exec` scenario field. Every HTTP call carries
// `type` (read/write) and `persona` tags so thresholds can slice cleanly.
//
// Flows (matches perf_test_plan.md §4):
//   F1 — donorDonationFlow         (login → donate → callback)
//   F2 — recipientRequestFlow      (login → browse → submit request)
//   F2 — recipientBrowseFlow       (login → list providers → view menu)  [read-only variant]
//   F3 — providerRedemptionFlow    (login → scan → redeem [JSON])
//   F4 — guestDonationFlow         (no login → POST /donate/initiate)
//   F5 — notificationsPollFlow     (login → GET /notifications)
//   F6 — publicLandingFlow         (anonymous landing / top-donors)
//   helper flows: donorDashboardFlow, providerMenuCrudFlow, authLoginFlow, adminActionsFlow
//
// Notes:
//   - Phone/email verification + OTP are intentionally skipped (out of scope).
//   - MyFatoorah redirect is NOT followed. We hit /payments/callback directly.
//   - Provider QR redeem rotates fake token strings to avoid the in-controller
//     per-token 2-attempts/30s limiter (see ProviderQrController::redeem).
//   - exec.scenario.progress is used to derive `phase` / `window` tags for
//     Spike and Soak.
// ---------------------------------------------------------------------------

import { check, sleep, group } from 'k6';
import exec from 'k6/execution';
import { SharedArray } from 'k6/data';
import papaparse from 'https://jslib.k6.io/papaparse/5.1.1/index.js';
import {
  BASE_URL,
  PAYMENT_CALLBACK_PATH,
  USERS_CSV_PATH,
  THINK_MIN,
  THINK_MAX,
  DONATION_MIN,
  DONATION_MAX,
  NO_SLEEP,
} from '../config/env.js';
import { get, getJson, postForm } from './http.js';
import { extractCsrfFast } from './csrf.js';
import { login } from './auth.js';
import { pickWeightedPersona } from './workload.js';

// ---- Test data ------------------------------------------------------------
// SharedArray loads the CSV once per VU pool. Defensive: if the file is
// missing, every flow that needs creds will fail fast in setup().

const users = new SharedArray('nubl_seed_users', () => {
  const raw = openCsv(USERS_CSV_PATH);
  if (!raw) return [];
  return papaparse.parse(raw, { header: true, skipEmptyLines: true }).data;
});

function openCsv(path) {
  try {
    return open(path);
  } catch (_e) {
    return null;
  }
}

function pickByRole(role) {
  const pool = users.filter((u) => u.role === role);
  if (pool.length === 0) {
    throw new Error(`No seed users found with role=${role}. ` +
      `Populate ${USERS_CSV_PATH} from tests/k6/data/users.example.csv.`);
  }
  // Spread VUs across the seed pool — vuId is 1-based.
  return pool[(exec.vu.idInTest - 1) % pool.length];
}

// ---- Tag helpers ----------------------------------------------------------
// Tag iterations by where they fall in the scenario timeline:
//   - Spike: `phase: surge | post_surge` (post_surge starts at progress > 0.65)
//   - Soak:  `window: early | mid | late` (early = first 25%, late = last 25%)

function spikePhase() {
  const p = exec.scenario.progress;
  return p > 0.65 ? 'post_surge' : 'surge';
}

function soakWindow() {
  const p = exec.scenario.progress;
  if (p <= 0.25) return 'early';
  if (p >= 0.75) return 'late';
  return 'mid';
}

/** Build a tag bag for a given flow step. Includes spike/soak tags if relevant. */
function tag(persona, type, step, extra = {}) {
  const t = { persona, type, step, ...extra };
  // Inexpensive — these always evaluate but only matter for the test that
  // tags on them.
  if (__ENV.NUBL_TAG_SPIKE === 'true') t.phase = spikePhase();
  if (__ENV.NUBL_TAG_SOAK === 'true') t.window = soakWindow();
  return t;
}

// ---- Misc helpers ---------------------------------------------------------
function think(min = THINK_MIN, max = THINK_MAX) {
  if (NO_SLEEP) return;
  const ms = (min + Math.random() * (max - min)) * 1000;
  sleep(ms / 1000);
}

function randomAmount() {
  return (DONATION_MIN + Math.random() * (DONATION_MAX - DONATION_MIN)).toFixed(2);
}

function randomToken() {
  // Random opaque string — Provider QR redeem hashes it before lookup, so
  // every iteration gets a fresh (404) miss → avoids the in-controller 2/30s
  // rate-limit on (provider+token) pairs.
  return `perftest-${exec.vu.idInTest}-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

function pickProviderId(html) {
  // Best-effort: extract a provider id from the listing page links.
  // Falls back to a stable default if nothing is found.
  const ids = [...(html || '').matchAll(/\/recipient\/providers\/(\d+)/g)]
    .map((m) => Number(m[1]))
    .filter((n) => n > 0);
  if (ids.length === 0) return null;
  return ids[Math.floor(Math.random() * ids.length)];
}

function pickMenuItemIds(html, limit = 2) {
  // Look for inputs / data attributes that expose menu-item ids on the
  // provider show page. Adjust the regex to your Blade template if needed.
  const ids = [...(html || '').matchAll(/data-menu-item-id="(\d+)"/g)]
    .map((m) => Number(m[1]));
  return ids.slice(0, limit);
}

// ===========================================================================
// F1 — Donor donation flow
// ===========================================================================
export function donorDonationFlow() {
  group('donor:donation_flow', () => {
    const creds = pickByRole('donor');
    login(creds.email, creds.password);

    const dash = get(`${BASE_URL}/donor/dashboard`, {
      tags: tag('donor', 'read', 'dashboard'),
    });
    check(dash, { 'donor dashboard 200': (r) => r.status === 200 });
    think();

    const newForm = get(`${BASE_URL}/donor/donations/new`, {
      tags: tag('donor', 'read', 'donation_form'),
    });
    check(newForm, { 'donation form 200': (r) => r.status === 200 });

    const csrf = extractCsrfFast(newForm.body);
    if (!csrf) return; // form unavailable — skip silently
    think(2, 5);

    const initiate = postForm(
      `${BASE_URL}/donor/payments/initiate`,
      {
        _token: csrf,
        amount: randomAmount(),
        is_anonymous: Math.random() < 0.2 ? '1' : '0',
      },
      {
        redirects: 0,
        tags: tag('donor', 'write', 'initiate_payment'),
      },
    );
    // PaymentService::redirectToGateway returns 302 to MyFatoorah. We trap
    // it here and do NOT follow into the gateway.
    check(initiate, {
      'initiate redirected (302)': (r) => r.status === 302,
    });

    // Simulate the sandbox callback returning to our app.
    const callback = get(
      `${BASE_URL}${PAYMENT_CALLBACK_PATH}?paymentId=perftest-${Date.now()}`,
      { tags: tag('donor', 'read', 'payment_callback') },
    );
    check(callback, {
      'callback handled (200/302/4xx)': (r) => r.status < 500,
    });
  });
}

// donorDashboardFlow — read-only variant for scenario weight #2
export function donorDashboardFlow() {
  group('donor:dashboard_only', () => {
    const creds = pickByRole('donor');
    login(creds.email, creds.password);

    const dash = get(`${BASE_URL}/donor/dashboard`, {
      tags: tag('donor', 'read', 'dashboard'),
    });
    check(dash, { 'donor dashboard 200': (r) => r.status === 200 });
    think();

    const list = get(`${BASE_URL}/donor/donations`, {
      tags: tag('donor', 'read', 'donations_list'),
    });
    check(list, { 'donations list 200': (r) => r.status === 200 });
  });
}

// ===========================================================================
// F2 — Recipient request + browse
// ===========================================================================
export function recipientBrowseFlow() {
  group('recipient:browse', () => {
    const creds = pickByRole('recipient');
    login(creds.email, creds.password);

    const dash = get(`${BASE_URL}/recipient/dashboard`, {
      tags: tag('recipient', 'read', 'dashboard'),
    });
    check(dash, { 'recipient dashboard 200': (r) => r.status === 200 });
    think();

    const providers = get(`${BASE_URL}/recipient/providers`, {
      tags: tag('recipient', 'read', 'providers_list'),
    });
    check(providers, { 'providers list 200': (r) => r.status === 200 });
    think();

    const providerId = pickProviderId(providers.body);
    if (!providerId) return;

    const show = get(`${BASE_URL}/recipient/providers/${providerId}`, {
      tags: tag('recipient', 'read', 'provider_show'),
    });
    check(show, { 'provider show 200': (r) => r.status === 200 });
  });
}

export function recipientRequestFlow() {
  group('recipient:request', () => {
    const creds = pickByRole('recipient');
    login(creds.email, creds.password);

    const providers = get(`${BASE_URL}/recipient/providers`, {
      tags: tag('recipient', 'read', 'providers_list'),
    });
    if (providers.status !== 200) return;

    const providerId = pickProviderId(providers.body);
    if (!providerId) return;
    think();

    const show = get(`${BASE_URL}/recipient/providers/${providerId}`, {
      tags: tag('recipient', 'read', 'provider_show'),
    });
    if (show.status !== 200) return;

    const csrf = extractCsrfFast(show.body);
    if (!csrf) return;
    const itemIds = pickMenuItemIds(show.body, 1 + Math.floor(Math.random() * 2));
    if (itemIds.length === 0) return; // no items available
    think(2, 5);

    // StoreRecipientRequest expects:
    //   provider_id, items[*][id], items[*][quantity]
    const body = { _token: csrf, provider_id: String(providerId) };
    itemIds.forEach((id, idx) => {
      body[`items[${idx}][id]`] = String(id);
      body[`items[${idx}][quantity]`] = '1';
    });

    const submit = postForm(`${BASE_URL}/recipient/requests`, body, {
      redirects: 0,
      tags: tag('recipient', 'write', 'submit_request'),
    });
    check(submit, {
      'request submit 302/200': (r) => [200, 302].includes(r.status),
    });
  });
}

// ===========================================================================
// F3 — Provider QR redemption  (JSON endpoint)
// ===========================================================================
export function providerRedemptionFlow() {
  group('provider:redemption', () => {
    const creds = pickByRole('provider');
    login(creds.email, creds.password);

    const scan = get(`${BASE_URL}/provider/qr/scan`, {
      tags: tag('provider', 'read', 'qr_scan_view'),
    });
    check(scan, { 'qr scan view 200': (r) => r.status === 200 });

    const csrf = extractCsrfFast(scan.body);
    if (!csrf) return;
    think(1, 2);

    // Redeem accepts JSON via the `_token` form field is not required for
    // JSON; instead, the X-XSRF-TOKEN header (decoded XSRF cookie) is used.
    // The simplest portable approach: send a form-encoded POST with _token.
    const redeem = postForm(
      `${BASE_URL}/provider/qr/redeem`,
      { _token: csrf, token: randomToken() },
      {
        tags: tag('provider', 'write', 'qr_redeem'),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      },
    );
    // Controller returns 200 with `success:false` on miss, 422 on validation,
    // 4xx on business-rule failure. All of these are *expected* during perf
    // testing because we send random tokens.
    check(redeem, {
      'redeem responded (<500)': (r) => r.status < 500,
    });
  });
}

// ===========================================================================
// F4 — Guest donation  (no auth)
// ===========================================================================
export function guestDonationFlow() {
  group('guest:donation', () => {
    // Hit the landing to seed XSRF-TOKEN cookie + read a CSRF token.
    const landing = get(`${BASE_URL}/`, {
      tags: tag('guest', 'read', 'landing'),
    });
    const csrf = extractCsrfFast(landing.body);
    // Guest donation form may live on the landing or a separate page —
    // try landing first, fall back to a fresh GET.
    const tokenToUse = csrf || extractCsrfFast(get(`${BASE_URL}/`).body);

    if (!tokenToUse) return;
    think();

    const res = postForm(
      `${BASE_URL}/donate/initiate`,
      { _token: tokenToUse, amount: randomAmount() },
      {
        redirects: 0,
        tags: tag('guest', 'write', 'donate_initiate'),
      },
    );
    check(res, {
      'guest donate redirected (302)': (r) => r.status === 302,
    });

    // Walk to the success page with a synthetic token.
    const success = get(
      `${BASE_URL}/donate/success?token=perftest-${Date.now()}`,
      { tags: tag('guest', 'read', 'donate_success') },
    );
    check(success, { 'success page 200': (r) => r.status === 200 });
  });
}

// ===========================================================================
// F5 — Notifications polling
// ===========================================================================
export function notificationsPollFlow() {
  group('cross:notifications_poll', () => {
    // Notifications poll is shared by all 3 personas — pick uniformly.
    const role = ['donor', 'recipient', 'provider'][Math.floor(Math.random() * 3)];
    const creds = pickByRole(role);
    login(creds.email, creds.password);

    const res = getJson(`${BASE_URL}/notifications`, {
      tags: tag('cross', 'read', 'notifications_index'),
    });
    check(res, {
      'notifications 200': (r) => r.status === 200,
      'notifications has unread_count': (r) => {
        try {
          return typeof r.json('unread_count') === 'number';
        } catch (_e) {
          return false;
        }
      },
    });
  });
}

// ===========================================================================
// F6 — Public landing / top donors
// ===========================================================================
export function publicLandingFlow() {
  group('public:landing', () => {
    const landing = get(`${BASE_URL}/`, {
      tags: tag('public', 'read', 'landing'),
    });
    check(landing, { 'landing 200': (r) => r.status === 200 });
    think(0.5, 2);

    const top = get(`${BASE_URL}/top-donors`, {
      tags: tag('public', 'read', 'top_donors'),
    });
    check(top, { 'top-donors 200': (r) => r.status === 200 });

    const feed = getJson(`${BASE_URL}/landing/feed`, {
      tags: tag('public', 'read', 'landing_feed'),
    });
    check(feed, { 'feed < 500': (r) => r.status < 500 });
  });
}

// ===========================================================================
// Provider menu CRUD — light writes from the provider portal
// ===========================================================================
export function providerMenuCrudFlow() {
  group('provider:menu_browse', () => {
    const creds = pickByRole('provider');
    login(creds.email, creds.password);

    const list = get(`${BASE_URL}/provider/menu-items`, {
      tags: tag('provider', 'read', 'menu_list'),
    });
    check(list, { 'menu list 200': (r) => r.status === 200 });
    think();

    const createForm = get(`${BASE_URL}/provider/menu-items/create`, {
      tags: tag('provider', 'read', 'menu_create_form'),
    });
    check(createForm, { 'menu create form 200/302': (r) => [200, 302].includes(r.status) });

    // Deliberately do NOT submit a create — schema unknown and we don't want
    // to inflate the menu table during perf runs. Reads only.
  });
}

// ===========================================================================
// Auth login (scenario weight #10) — pure login + dashboard fetch
// ===========================================================================
export function authLoginFlow() {
  group('auth:login_only', () => {
    const role = ['donor', 'recipient', 'provider'][Math.floor(Math.random() * 3)];
    const creds = pickByRole(role);
    login(creds.email, creds.password);

    const dash = get(`${BASE_URL}/dashboard`, {
      tags: tag('auth', 'read', 'dashboard_redirect'),
    });
    check(dash, { 'post-login dashboard < 500': (r) => r.status < 500 });
  });
}

// ===========================================================================
// Admin actions — read-only sample (dashboard + audit logs)
// ===========================================================================
export function adminActionsFlow() {
  group('admin:read', () => {
    let creds;
    try {
      creds = pickByRole('admin');
    } catch (_e) {
      return; // optional persona — skip if no admin seeded
    }
    login(creds.email, creds.password);

    const dash = get(`${BASE_URL}/admin/dashboard`, {
      tags: tag('admin', 'read', 'dashboard'),
    });
    check(dash, { 'admin dashboard 200': (r) => r.status === 200 });
    think();

    const logs = get(`${BASE_URL}/admin/audit-logs`, {
      tags: tag('admin', 'read', 'audit_logs'),
    });
    check(logs, { 'audit logs 200': (r) => r.status === 200 });
  });
}

// ===========================================================================
// Weighted dispatcher — used by Spike + Stress scripts.
// ===========================================================================
// Maps the flow names from lib/workload.js distribution to actual functions.

const _flowMap = {
  recipientBrowseFlow,
  donorDashboardFlow,
  guestDonationFlow,
  recipientRequestFlow,
  donorDonationFlow,
  providerRedemptionFlow,
  notificationsPollFlow,
  providerMenuCrudFlow,
  publicLandingFlow,
  authLoginFlow,
  adminActionsFlow,
};

/** Pick a flow per the workload distribution and run it. */
export function weightedJourney() {
  const entry = pickWeightedPersona();
  const fn = _flowMap[entry.fn];
  if (fn) fn();
}
