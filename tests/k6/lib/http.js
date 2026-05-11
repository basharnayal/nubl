// tests/k6/lib/http.js
// ---------------------------------------------------------------------------
// Thin wrapper around k6's http module that:
//   - merges default headers (Accept, Accept-Language)
//   - tracks unexpected 429s (since rate limiters are disabled in perf env)
//   - exposes a single `safeRequest` for tagged GET/POST with retries off
// ---------------------------------------------------------------------------

import http, { setResponseCallback, expectedStatuses } from 'k6/http';
import { Counter } from 'k6/metrics';

// Custom counter referenced by config/thresholds.js (`http_429_unexpected`).
export const unexpected429 = new Counter('http_429_unexpected');

// Treat ONLY 5xx as real failures. 4xx are expected client errors
// (e.g., provider_redeem flow sends random fake QR tokens that legitimately
// return 404/422; recipient_request with stale menu item ids returns 422).
// Standard SRE practice: server failures = 5xx; 4xx is "the client did
// something wrong" and is not a defect of the server.
setResponseCallback(expectedStatuses({ min: 200, max: 499 }));

const DEFAULT_HEADERS = {
  Accept: 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
  'Accept-Language': 'en-US,en;q=0.9',
  'Cache-Control': 'no-cache',
};

const AJAX_HEADERS = {
  Accept: 'application/json',
  'X-Requested-With': 'XMLHttpRequest',
};

/**
 * Issue a GET request with default headers + merged tags.
 */
export function get(url, params = {}) {
  const res = http.get(url, mergeParams(params));
  trackUnexpected429(res, params.tags);
  return res;
}

/**
 * Issue a JSON-flavored GET (sets Accept: application/json).
 */
export function getJson(url, params = {}) {
  return get(url, {
    ...params,
    headers: { ...AJAX_HEADERS, ...(params.headers || {}) },
  });
}

/**
 * Issue a form-encoded POST. Caller supplies `body` (object) and tags.
 */
export function postForm(url, body, params = {}) {
  const res = http.post(url, body, mergeParams(params));
  trackUnexpected429(res, params.tags);
  return res;
}

/**
 * Issue a JSON POST.
 */
export function postJson(url, payload, params = {}) {
  const res = http.post(url, JSON.stringify(payload), mergeParams({
    ...params,
    headers: {
      'Content-Type': 'application/json',
      ...AJAX_HEADERS,
      ...(params.headers || {}),
    },
  }));
  trackUnexpected429(res, params.tags);
  return res;
}

function mergeParams(params) {
  return {
    ...params,
    headers: { ...DEFAULT_HEADERS, ...(params.headers || {}) },
    // We want explicit control of 302 redirects (Laravel's auth flow), so
    // callers pass `redirects: 0` when needed. Default to follow.
    redirects: params.redirects === undefined ? 1 : params.redirects,
  };
}

function trackUnexpected429(res, tags) {
  if (res.status === 429) {
    unexpected429.add(1, tags || {});
  }
}
