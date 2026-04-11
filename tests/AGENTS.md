# E2E Testing Guidelines

- We use Playwright with JavaScript (`.spec.js` under `tests/e2e/`).
- Tests are located in the `tests/e2e` directory.
- The `baseURL` is configured in `config/playwright/*.config.js` to `http://127.0.0.1:8001` (must match `php artisan serve` in that config). Do not hardcode the host in `page.goto()`; use relative paths (e.g., `await page.goto('/login')`).
- Use web-first assertions (e.g., `await expect(page.locator('.alert')).toBeVisible()`).
- Rely on Playwright's auto-waiting capabilities; avoid adding hard `page.waitForTimeout()` sleeps.
- Validate backend state changes where necessary (you can assume the app is a standard Laravel MVC or API architecture).