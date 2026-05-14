# NUBL — Digital Assistance Platform [![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2F5c873e99-e66e-4ddc-b53d-bedab32070f8%3Fdate%3D1%26label%3D1%26commit%3D1&style=plastic)](https://forge.laravel.com/bashar-gbv/nubl-digitaloceanserver/3152908)

A digital sadaqah platform that connects **donors**, **recipients**, and **providers** in a dignified, private, and transparent way.

Donors fund a shared pool, recipients submit assistance requests that are allocated against a weekly allowance, and approved requests become short-lived QR tokens that recipients redeem at partner providers (supermarkets / restaurants). Every sensitive action is audit-logged.

---

## Technology Stack

| Layer | Choice |
|-------|--------|
| Backend | Laravel 12 (PHP 8.2+, `ext-bcmath` required for wallet math) |
| Frontend | Blade templates + Tailwind CSS v4 + Alpine.js (Lineone theme) |
| Build | Vite |
| Database | MySQL (SQLite used for local tests) |
| Auth | Laravel Sanctum |
| Authorization | Spatie Laravel Permission (roles **and** granular permissions) |
| Audit | Spatie Laravel Activity Log |
| Payments | MyFatoorah |
| SMS / OTP | Taqnyat |
| PDF / Excel | barryvdh/laravel-dompdf, PhpSpreadsheet |
| QR codes | simplesoftwareio/simple-qrcode |
| Testing | PHPUnit (Feature + Unit), Playwright (E2E), k6 (load) |

---

## Setup

**Prerequisites:** PHP 8.2+, Composer, Node.js 20.19+ (or 22.12+), MySQL.

```bash
composer install
npm install

cp .env.example .env            # Windows: copy .env.example .env
php artisan key:generate
# edit .env: DB credentials, MyFatoorah keys, Taqnyat SMS, mail

php artisan migrate
php artisan db:seed --class=PermissionSeeder   # permissions first
php artisan db:seed --class=RoleSeeder         # then roles
php artisan storage:link

npm run build                   # or: npm run dev (during development)
php artisan serve
```

> `php artisan db:seed` (the full `DatabaseSeeder`) also creates demo users, providers, and sample requests — useful for local exploration, not for production.

For full local dev (server + queue worker + logs + Vite in one process): `composer run dev`.

---

## Roles

Managed by Spatie Permission, seeded by `RoleSeeder` (permissions by `PermissionSeeder`).

| Role | Description |
|------|-------------|
| `admin` | System administrator — manages users, finances, allocation, settings |
| `donor` | Donor (Gracious Neighbor) — funds the pool |
| `recipient` | Recipient (Neighbor) — submits assistance requests |
| `provider` | Provider — supermarket / restaurant that fulfils redemptions |

Each user has exactly one role assigned at registration. **Recipient and provider accounts require admin approval** before they can log in. The `User` model uses the `HasRoles` trait. Beyond the role gate, the admin panel enforces **fine-grained permissions** (e.g. `users.update`, `finance.manage`, `audit_logs.view`) defined in `app/Support/PermissionDefinitions.php`.

---

## Project Structure

```
nubl/
├── app/
│   ├── Auth/                 # Registration pipeline (registrars + data objects), post-auth redirect
│   ├── Console/Commands/     # Artisan / scheduled commands
│   ├── Contracts/            # Interfaces
│   ├── Http/
│   │   ├── Controllers/      # Thin controllers — Admin/ Auth/ Donor/ Provider/ Recipient/ Testing/
│   │   ├── Middleware/       # Role/permission/approval/locale guards
│   │   └── Requests/         # Form Request validation
│   ├── Jobs/                 # Queued jobs (allocation retries, etc.)
│   ├── Models/               # Eloquent models
│   ├── Notifications/        # User notifications
│   ├── Observers/            # Model observers
│   ├── Providers/            # Service providers (rate limiters, bindings)
│   ├── Rules/                # Custom validation rules
│   ├── Services/             # Business logic — Admin/ subfolder for admin-panel services
│   ├── Support/              # Stateless helpers (formatting, math, value objects)
│   └── View/                 # Blade Components/ and Composers/
│
├── config/                   # App config + config/playwright/ for E2E
├── database/
│   ├── factories/  migrations/  seeders/
│
├── resources/
│   ├── css/  js/
│   └── views/                # admin/ auth/ components/ donor/ guest-donation/
│                             # legal/ partials/ profile/ provider/ recipient/ top-donors/
│
├── routes/
│   ├── web.php               # All app routes, grouped by role prefix + middleware
│   ├── auth.php              # Authentication (login, register, OTP, password reset)
│   ├── console.php           # Scheduled commands
│   └── testing.php           # Test-only endpoints (non-production)
│
├── lang/                     # en/ and ar/ translation files
└── tests/
    ├── Feature/  Unit/        # PHPUnit (RefreshDatabase)
    ├── e2e/                   # Playwright browser specs
    ├── k6/                    # k6 load-test scripts
    └── performance/           # Performance test configs
```

---

## Architecture

The codebase follows a layered MVC + service-layer design:

- **Controllers** (`app/Http/Controllers/`) — thin, grouped by role: `Admin/`, `Donor/`, `Recipient/`, `Provider/`, `Auth/`.
- **Services** (`app/Services/`) — business logic. Anything beyond a couple of lines lives here, not in controllers. Admin-panel services sit under `app/Services/Admin/`.
- **Form Requests** (`app/Http/Requests/`) — validation. Controllers never call `$request->validate()` directly.
- **Models** (`app/Models/`) — Eloquent. Status/enum constants are defined on the model (e.g. `User::STATUS_ACTIVE`, `Payment::STATUS_SUCCEEDED`).
- **Views** (`resources/views/`) — Blade only, no Vue/React/Livewire. `x-app-layout` for authenticated pages (Lineone sidebar), `x-guest-layout` for auth/landing pages.
- **Routes** (`routes/web.php`) — grouped by role prefix and middleware; reusable middleware stacks are defined once at the top of the file. Auth routes are split into `routes/auth.php`.

### Middleware pipeline

```
auth → phone.verified → account.approved → role:{role}
```

- `phone.verified` (OTP) is conditional on `config('app.phone_verification_enabled')`.
- Admin routes layer granular `permission:{ability}` checks on top of `role:admin`.
- `SetLocale` (switches `en` / `ar`) and `DisableHttpCacheForAuthForms` run on the whole `web` group.
- Aliases are registered in `bootstrap/app.php`: `role`, `permission`, `redirect.by.role`, `phone.verified`, `account.approved`, `email.verified`.

### Audit logging

Important state changes go through `App\Services\AuditService::log($entity, $action, $data, $userId)`, which wraps Spatie Activity Log. Entries land in the `activity_log` table with the actor, IP, user agent, and an integrity hash. Direct `activity()->log(...)` calls are allowed for one-off events.

### Payments

Donations are initiated via `PaymentService` and dispatched to MyFatoorah. The gateway callback flows back through `PaymentCallbackController`, which marks the `Payment` row, credits the system wallet (`FundTransaction`), and triggers allowance allocation. Rate limiting on the callback and donation endpoints is configured in `app/Providers/RateLimiterServiceProvider.php`.

### QR redemption

When a recipient request is approved, a short-lived QR token is generated (TTL from `config/qr.php`, overridable per-deployment via the `SystemSetting` key `qr.ttl_minutes`). The provider scans it via `ProviderQrController`, and `RedemptionService` moves the request to `FULFILLED` and records the redemption.

---

## Configuration

Project-specific tunables (clear cache with `php artisan optimize:clear` after changing config files):

| Setting | Where | Default |
|---------|-------|---------|
| Recipient weekly allowance limit (SAR) | `config/provider.php` → `recipient.weekly_allowance_limit` | `600` |
| QR redemption window (minutes) | `config/qr.php` → `ttl_minutes` (env `QR_TTL_MINUTES`) | `180` |
| Phone/OTP verification step | `config/app.php` → `phone_verification_enabled` | `true` |

Admin-overridable values (weekly allowance, QR TTL, maintenance mode) are stored in the `system_settings` table and take precedence over the config defaults.

---

## Testing

### PHPUnit

```bash
php artisan test
# or
composer test
```

Feature tests live under `tests/Feature/`, unit tests under `tests/Unit/`. Both use `RefreshDatabase`.

### Playwright (E2E)

Specs live in `tests/e2e/`. First-time setup: `npx playwright install chromium`.

| Command | What it runs |
|---------|--------------|
| `npm run test:e2e` | Laravel (port 8001) + Vite, full browser flow |
| `npm run test:e2e:php-only` | Laravel only (no Vite asset rebuild) |
| `npm run test:e2e:ui` | Playwright UI mode for debugging |
| `npm run test:e2e:headed` | Headed browser run |

### Load testing

k6 scripts and performance configs live under `tests/k6/` and `tests/performance/`.
