// tests/k6/lib/auth.js
// ---------------------------------------------------------------------------
// Laravel Sanctum session-cookie login flow.
//
// nubl uses Sanctum in "Blade SPA" mode: every authenticated POST/PUT/PATCH/
// DELETE must carry a valid `_token` CSRF field, and the `laravel_session`
// cookie is set automatically. k6 manages cookies per-VU in an in-memory jar.
//
// Flow:
//   1. GET /login   → read CSRF token from the rendered form
//   2. POST /login  → email + password + _token  (expects 302 to dashboard)
//
// After step 2 the VU's cookie jar carries `laravel_session` and `XSRF-TOKEN`
// for the rest of the iteration.
// ---------------------------------------------------------------------------

import { check, fail } from 'k6';
import { get, postForm } from './http.js';
import { extractCsrfFast } from './csrf.js';
import { BASE_URL } from '../config/env.js';

/**
 * Authenticate a VU with email + password. Returns the CSRF token the caller
 * should reuse for any subsequent POST/PUT/PATCH in the same iteration.
 *
 * @param {string} email
 * @param {string} password
 * @returns {string|null} CSRF token from the post-login dashboard, or null
 *                        if the caller chooses to re-fetch later.
 */
export function login(email, password) {
  // (1) Fetch login page to seed XSRF-TOKEN cookie + read _token field
  const loginPage = get(`${BASE_URL}/login`, {
    tags: { type: 'read', flow: 'auth', step: 'login_get' },
  });

  const ok1 = check(loginPage, {
    'login page 200': (r) => r.status === 200,
  });
  if (!ok1) {
    fail(`GET /login failed: status=${loginPage.status}`);
  }

  const csrf = extractCsrfFast(loginPage.body);
  if (!csrf) {
    fail('CSRF token not found on /login');
  }

  // (2) POST credentials. Laravel returns 302 → /dashboard (or role-specific
  // dashboard) on success, 302 → /login with errors on failure.
  const res = postForm(
    `${BASE_URL}/login`,
    { _token: csrf, email, password, remember: 'on' },
    {
      redirects: 0,
      tags: { type: 'write', flow: 'auth', step: 'login_post' },
    },
  );

  const ok2 = check(res, {
    'login redirected (302)': (r) => r.status === 302,
    'login did not bounce back to /login': (r) =>
      !((r.headers['Location'] || '').endsWith('/login')),
  });
  if (!ok2) {
    fail(`POST /login failed: status=${res.status} location=${res.headers['Location']}`);
  }

  return csrf;
}

/**
 * Logout. Best-effort — failures here are tagged but not fatal.
 */
export function logout(csrf) {
  return postForm(
    `${BASE_URL}/logout`,
    { _token: csrf },
    { tags: { type: 'write', flow: 'auth', step: 'logout' } },
  );
}

/**
 * Visit a given page and return the freshly-parsed CSRF token from its body.
 * Used by flows that need a new token (e.g., before submitting a form on
 * page N that wasn't the immediate prior request).
 */
export function refreshCsrf(url) {
  const res = get(url, {
    tags: { type: 'read', flow: 'auth', step: 'refresh_csrf' },
  });
  return extractCsrfFast(res.body);
}
