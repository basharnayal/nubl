// tests/k6/lib/csrf.js
// ---------------------------------------------------------------------------
// Extract Laravel's CSRF token from a rendered Blade response.
// Laravel embeds the token in two places:
//   1. <input type="hidden" name="_token" value="...">  (form fields)
//   2. <meta name="csrf-token" content="...">           (for AJAX)
// We try (1) first, fall back to (2).
// ---------------------------------------------------------------------------

import { parseHTML } from 'k6/html';

export function extractCsrf(html) {
  if (!html) return null;
  const doc = parseHTML(html);

  // Prefer the hidden input
  const fromInput = doc.find('input[name="_token"]').first().attr('value');
  if (fromInput) return fromInput;

  // Fallback to meta tag
  const fromMeta = doc.find('meta[name="csrf-token"]').first().attr('content');
  return fromMeta || null;
}

/**
 * Cheap regex fallback in case parseHTML is unavailable or expensive.
 * Returns null if neither pattern matches.
 */
export function extractCsrfFast(html) {
  if (!html) return null;
  const m1 = html.match(/name="_token"\s+value="([^"]+)"/);
  if (m1) return m1[1];
  const m2 = html.match(/<meta\s+name="csrf-token"\s+content="([^"]+)"/);
  return m2 ? m2[1] : null;
}
