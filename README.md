# NUBL - Digital Assistance Platform [![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2F5c873e99-e66e-4ddc-b53d-bedab32070f8%3Fdate%3D1%26label%3D1%26commit%3D1&style=plastic)](https://forge.laravel.com/bashar-gbv/nubl-digitaloceanserver/3152908)

A digital sadaqah platform that connects donors, beneficiaries, and providers in a dignified, private, and transparent way.

Stack: Laravel 12 (PHP 8.2+), Blade + Tailwind v4 + Alpine.js, MySQL, Vite. Auth via Sanctum, RBAC via Spatie Permission, audit via Spatie Activity Log, payments via MyFatoorah, SMS via Taqnyat, E2E via Playwright.

---

## Setup

Prerequisites: PHP 8.2+, Composer, Node 20.19+ (or 22.12+), MySQL.

```bash
composer install
npm install

cp .env.example .env            # Windows: copy .env.example .env
php artisan key:generate
# edit .env: DB, MyFatoorah keys, Taqnyat SMS, mail

php artisan migrate
php artisan db:seed --class=RoleSeeder
php artisan storage:link

npm run build                   # or: npm run dev (during development)
php artisan serve
```

For full local dev (server + queue + logs + Vite in one process): `composer run dev`.

---

## Roles

Managed by Spatie Permission. Seeded by `RoleSeeder`.

| Role | Description |
|------|-------------|
| `admin` | System administrator |
| `donor` | Donor (Gracious Neighbor) |
| `recipient` | Recipient (Neighbor) |
| `provider` | Provider (supermarket / restaurant) |

Each user has exactly one role assigned at registration. Recipient and provider accounts require admin approval before they can log in. The `User` model uses the `HasRoles` trait; the role check middleware is `EnsureRole` (`app/Http/Middleware/EnsureRole.php`).

---

## Architecture

- **Controllers** (`app/Http/Controllers/`) — thin, grouped by role: `Admin/`, `Donor/`, `Recipient/`, `Provider/`, `Auth/`.
- **Services** (`app/Services/`) — business logic. Anything beyond a couple of lines lives here, not in controllers.
- **Form Requests** (`app/Http/Requests/`) — validation. Controllers never call `$request->validate()` directly.
- **Models** (`app/Models/`) — Eloquent. Status/enum constants are defined on the model (e.g. `User::STATUS_ACTIVE`, `Payment::STATUS_SUCCEEDED`).
- **Views** (`resources/views/`) — Blade only, no Vue/React/Livewire. `x-app-layout` for authenticated pages, `x-guest-layout` for auth/landing.
- **Routes** (`routes/web.php`) — grouped by role prefix and middleware. Auth routes are split into `routes/auth.php`.

### Middleware pipeline

`auth` → `EnsurePhoneVerified` (OTP) → `EnsureAccountApproved` → `EnsureRole`. Optional `EnsureEmailVerified` toggled via `.env`. `SetLocale` switches between `en` and `ar`.

### Audit logging

Important state changes go through `App\Services\AuditService::log($entity, $action, $data, $userId)`, which wraps Spatie Activity Log. Entries land in the `activity_log` table with the actor, IP, user agent, and an integrity hash. Direct `activity()->log(...)` calls are allowed for one-off events.

### Payments

Donations are initiated via `PaymentService` and dispatched to MyFatoorah. The gateway callback flows back through `PaymentCallbackController`, which marks the `Payment` row, credits the system wallet (`FundTransaction`), and triggers allowance allocation. Rate limiting on the callback and donation endpoints is configured in `app/Providers/RateLimiterServiceProvider.php`.

### QR redemption

When a recipient request is approved, a short-lived QR token is generated (TTL from `config/qr.php`, overridable via `SystemSetting` key `qr.ttl_minutes`). The provider scans it via `ProviderQrController`, and `RedemptionService` moves the request to `FULFILLED` and records the redemption.

---

## Testing

### PHPUnit

```bash
php artisan test
# or
composer test
```

Feature tests are under `tests/Feature/`, unit tests under `tests/Unit/`. Both use `RefreshDatabase`.

### Playwright (E2E)

Specs live in `tests/e2e/`. Two configs in `config/playwright/`:

| Command | What it runs |
|---------|--------------|
| `npm run test:e2e` | Laravel (port 8001) + Vite, full browser flow |
| `npm run test:e2e:php-only` | Laravel only (no Vite asset rebuild) |
| `npm run test:e2e:ui` | Playwright UI mode for debugging |

First-time setup: `npx playwright install chromium`.
