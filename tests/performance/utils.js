/**
 * Shared utilities for nubl k6 performance tests.
 * Handles CSRF token fetching and session-based login for Laravel.
 */

import http from 'k6/http';
import { check } from 'k6';

export const BASE_URL = __ENV.BASE_URL || 'https://nublhope.com';

/**
 * Fetch CSRF token from a given page (e.g. /login).
 * Laravel embeds the token in a <meta name="csrf-token"> tag.
 */
export function getCsrfToken(path = '/login') {
    const res = http.get(`${BASE_URL}${path}`);
    const match = res.body.match(/meta name="csrf-token" content="([^"]+)"/);
    return match ? match[1] : null;
}

/**
 * Log in as a user and return the session jar (cookies).
 * Returns { jar, csrfToken } or null on failure.
 */
export function login(email, password) {
    const jar = http.cookieJar();
    const csrfToken = getCsrfToken('/login');

    if (!csrfToken) return null;

    const res = http.post(
        `${BASE_URL}/login`,
        {
            email: email,
            password: password,
            _token: csrfToken,
        },
        {
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken,
            },
            jar: jar,
            redirects: 5,
        }
    );

    const success = check(res, {
        'login succeeded (no login page in response)': (r) =>
            !r.url.includes('/login'),
    });

    return success ? { jar, csrfToken } : null;
}

/**
 * Get a fresh CSRF token from any authenticated page.
 * Use this after login to get the token for POST requests.
 */
export function getAuthCsrfToken(jar, path = '/') {
    const res = http.get(`${BASE_URL}${path}`, { jar });
    const match = res.body.match(/meta name="csrf-token" content="([^"]+)"/);
    return match ? match[1] : null;
}
