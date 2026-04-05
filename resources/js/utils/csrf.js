/**
 * CSRF token for JavaScript (axios/fetch).
 * Prefer the XSRF-TOKEN cookie when EncryptCookies excludes it (see bootstrap/app.php);
 * otherwise fall back to the meta tag rendered by Blade.
 */
export function getCsrfToken() {
  if (typeof document === 'undefined') {
    return '';
  }

  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
  if (match) {
    try {
      return decodeURIComponent(match[1]);
    } catch {
      /* ignore */
    }
  }

  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}
