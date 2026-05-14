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
| Cache / Session / Queue | `database` drivers by default; Redis (predis client) in the Docker stack |
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

### Option 1 — Docker (recommended)

Docker provides the app, MySQL, and Redis in one stack — no local services needed. **Stop Laragon (or any local MySQL/Redis) first** to avoid port conflicts.

```bash
cp .env.example .env            # Windows: copy .env.example .env
docker compose up -d --build
```

The container configures itself on first boot (composer install, npm install, build, key generation, **migrations**) — first run takes ~2 minutes, after that it's instant. The app is then available at **http://localhost:8000**.

Migrations run automatically, but seeders do not — seed the database once after the first start:

```bash
docker compose exec app php artisan db:seed
```

| Task | Command |
|------|---------|
| Start | `docker compose up -d --build` |
| Stop | `docker compose down` |
| Reset (drop all data) | `docker compose down -v` |
| Logs | `docker compose logs app --tail 50 -f` |
| Artisan / Tinker | `docker compose exec app php artisan <cmd>` |
| Run tests | `docker compose exec app php artisan test` |
| Vite dev server (frontend work) | `docker compose --profile dev up vite` |

Cache, session, and queue run on Redis inside the stack. Full details, the reference `.env`, and the Redis rationale are in [docs/DOCKER.md](docs/DOCKER.md).

### Option 2 — Manual / local

**Prerequisites:** PHP 8.2+, Composer, Node.js 20.19+ (or 22.12+), MySQL.

```bash
composer install
npm install

cp .env.example .env            # Windows: copy .env.example .env
php artisan key:generate
# edit .env: DB credentials, MyFatoorah keys, Taqnyat SMS, mail

php artisan migrate --seed       # DatabaseSeeder: permissions, roles, core users, providers, menu items
php artisan storage:link

npm run build                   # or: npm run dev (during development)
php artisan serve
```

> `migrate --seed` runs `DatabaseSeeder`, which creates base data plus an `admin@nubl.com` / `password` login. For the larger demo dataset (sample donations, requests, payouts), run `php artisan db:seed --class=DemoDataSeeder`. Full seed reference and test accounts: [docs/SEEDING.md](docs/SEEDING.md).

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
| Phone / OTP verification step | `config/app.php` → `phone_verification_enabled` (env `PHONE_VERIFICATION_ENABLED`) | `true` |
| Email verification step | `config/app.php` → `email_verification_enabled` (env `EMAIL_VERIFICATION_ENABLED`) | `true` |
| HTTP rate limiting | `config/rate_limiting.php` (env `RATE_LIMITING_ENABLED`) | `true` |

Admin-overridable values (weekly allowance, QR TTL, maintenance mode) are stored in the `system_settings` table and take precedence over the config defaults.

---

## Documentation

Detailed guides live in the `docs/` folder. Start with [docs/README_DEV.md](docs/README_DEV.md) — the central developer reference (commit conventions, role/permission usage, Lineone UI components).

| Topic | Document |
|-------|----------|
| Docker environment | [DOCKER.md](docs/DOCKER.md) |
| Seed data & test accounts | [SEEDING.md](docs/SEEDING.md) |
| Roles & permissions (RBAC) | [PERMISSIONS_AND_RBAC_AR.md](docs/PERMISSIONS_AND_RBAC_AR.md) |
| Deployment | [DEPLOYMENT.md](docs/DEPLOYMENT.md) |
| Payments & MyFatoorah | [PAYMENT_REFERENCE.md](docs/PAYMENT_REFERENCE.md) |
| QR redemption flow | [QR_CODE_REDEMPTION.md](docs/QR_CODE_REDEMPTION.md) |
| Recipient weekly allowance | [RECIPIENT_WEEKLY_ALLOWANCE.md](docs/RECIPIENT_WEEKLY_ALLOWANCE.md) |
| Request lifecycle & statuses | [REQUEST_STATUSES.md](docs/REQUEST_STATUSES.md) |
| Audit logging | [AUDIT_LOG_GUIDE_AR.md](docs/AUDIT_LOG_GUIDE_AR.md) |
| HTTP rate limiting | [RATE_LIMITING.md](docs/RATE_LIMITING.md) |
| Notifications | [NOTIFICATIONS.md](docs/NOTIFICATIONS.md) |

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
