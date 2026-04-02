# E2E Testing Guidelines

- We use Playwright with TypeScript.
- Tests are located in the `tests/e2e` directory.
- The `baseURL` is already configured to `http://127.0.0.1:8000`. Do not hardcode the domain in `page.goto()`, use relative paths (e.g., `await page.goto('/login')`).
- Use web-first assertions (e.g., `await expect(page.locator('.alert')).toBeVisible()`).
- Rely on Playwright's auto-waiting capabilities; avoid adding hard `page.waitForTimeout()` sleeps.
- Validate backend state changes where necessary (you can assume the app is a standard Laravel MVC or API architecture).