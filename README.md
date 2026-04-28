# NUBL - Digital Assistance Platform [![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2F5c873e99-e66e-4ddc-b53d-bedab32070f8%3Fdate%3D1%26label%3D1%26commit%3D1&style=plastic)](https://forge.laravel.com/bashar-gbv/nubl-digitaloceanserver/3152908)

A digital platform for assistance (sadaqah) that connects donors, beneficiaries, and providers in a dignified, private, and transparent manner.

---

## 🛠️ Technology Stack

- **Backend**: Laravel 12 (PHP 8.2+) latest version
- **Frontend**: Blade Templates 
- **CSS Framework**: Tailwind CSS v4
- **UI Components**: Lineone (Alpine.js + Tailwind)
- **JavaScript**: Alpine.js
- **Build Tool**: Vite
- **End-to-end tests**: Playwright (`tests/e2e/`, config in `config/playwright/`)
- **Authentication**: Laravel Sanctum
- **Authorization**: Spatie Laravel Permission
- **Audit Logging**: Spatie Laravel Activity Log
- **Database**: MySQL

---

## 📋 Roles

This project uses **Spatie Laravel Permission** for role management:

- **admin** - System administrator
- **donor** - Donor (Gracious Neighbor)
- **recipient** - Recipient (Neighbor)
- **provider** - Provider (Local supermarkets/restaurants)

---

## 🚀 Quick Setup

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js 20.19+ or 22.12+
- MySQL

### Setup Steps

```bash
# 1. Install PHP Dependencies
composer install

# 2. Install NPM Dependencies
npm install

# 3. Environment Setup
# Windows (cmd/PowerShell): copy .env.example .env
# macOS/Linux: cp .env.example .env
copy .env.example .env
php artisan key:generate

# Edit .env with your database credentials, MyFatoorah keys, SMS (Taqnyat), etc.

# 4. Database Setup
php artisan migrate
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan db:seed --class=RoleSeeder

# Activity Log migrations are auto-loaded; run migrate if not yet run

# 5. Build Assets
npm run build

# 6. Create Storage Link
php artisan storage:link

# 7. Start Development Servers
# Terminal 1: Laravel Server
php artisan serve

# Terminal 2: Vite Dev Server (for development only)
npm run dev
```
./vendor/bin/pint --test

### Assign Role to User

```bash
php artisan tinker
```

In Tinker:
```php
$user = User::where('email', 'your-email@example.com')->first();
$user->assignRole('admin');
```

---

## 🚦 Essential Commands

| Task | Command |
|------|---------|
| Model + Migration | `php artisan make:model Name -m` |
| Model (everything) | `php artisan make:model Name -a` |
| Controller | `php artisan make:controller Name --resource` |
| Controller in subfolder | `php artisan make:controller Admin/DashboardController` |
| Form Request | `php artisan make:request Name` |
| Seeder | `php artisan make:seeder Name` |
| Middleware | `php artisan make:middleware Name` |
| Event | `php artisan make:event Name` |
| Listener | `php artisan make:listener Name --event=Event` |
| Migration | `php artisan make:migration create_table_name_table` |
| Run Migrations | `php artisan migrate` |
| Run Seeder | `php artisan db:seed --class=Name` |
| Clear Cache | `php artisan optimize:clear` |
| Build Assets | `npm run build` |
| Dev Server | `npm run dev` |
| Full stack (server + queue + logs + Vite) | `composer run dev` |
| PHPUnit | `php artisan test` or `composer test` |
| E2E (Playwright, Laravel + Vite) | `npm run test:e2e` |
| E2E (Playwright, PHP server only) | `npm run test:e2e:php-only` |
| List Routes | `php artisan route:list` |
| Tinker | `php artisan tinker` |
| Queue Worker | `php artisan queue:work` |

---

## 🏗️ Project Structure

### Folder Structure

```
nubl/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # Controllers (organized by role)
│   │   │   ├── Admin/
│   │   │   ├── Donor/
│   │   │   ├── recipient/
│   │   │   └── Provider/
│   │   ├── Middleware/           # Custom Middleware
│   │   ├── Requests/             # Form Requests (Validation)
│   └── Services/                 # Business Logic Layer
│   ├── Models/                   # Eloquent Models
│   ├── Providers/                # Service Providers
│   └── View/Components/          # Blade Component Classes
│
├── database/
│   ├── migrations/
│   └── seeders/
│
├── resources/
│   ├── css/
│   │   └── app.css               # Tailwind CSS
│   ├── js/
│   │   ├── app.js                # Main JS file
│   │   └── bootstrap.js          # Bootstrap JS
│   └── views/
│       ├── layouts/              # Layout Templates
│       ├── components/           # Blade Components
│       ├── admin/                # Admin Views
│       ├── donor/                # Donor Views
│       ├── recipient/            # Recipient Views
│       └── provider/             # Provider Views
│
├── routes/
│   ├── web.php                   # Main web routes (role groups;        includes auth)
│   ├── auth.php                  # Auth routes (required from web.php)
│   └── console.php
│
└── tests/
    ├── e2e/                      # Playwright specs (*.spec.js)
    ├── Feature/                  # PHPUnit
    └── Unit/
```

### Architecture Pattern

- **MVC Pattern**: Model-View-Controller
- **Service Layer**: Business logic in `app/Services/` 
- **Form Requests**: Validation in `app/Http/Requests/`
- **Blade Templates**: Server-side rendering (no SPA)

---

## 🎨 Frontend Configuration

### Vite Configuration

**`vite.config.js`**
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

### CSS & JavaScript

- **Tailwind v4** with Lineone theme (colors, components)
- **Alpine.js** with plugins (persist, collapse, intersect)
- **Vite** for bundling (`npm run dev` / `npm run build`)

### Important Notes

- **Tailwind v4** uses CSS-first configuration (no `tailwind.config.js` needed)
- **Lineone** components: `x-lineone-button`, `x-lineone-modal`, `x-lineone-alert`, `x-lineone-card`
- **Buttons**: Use `x-lineone-button` (variants: primary, danger, success, etc.) for app pages. `x-primary-button` and `x-danger-button` are thin wrappers for auth/profile forms.
- **Vite** must be running (`npm run dev`) during development
- Use `npm run build` for production

---

## 📖 Spatie Permission Usage

### In Routes

Protect routes with specific roles:
```php
// Single role
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', ...);
});

// Multiple roles (OR)
Route::middleware(['auth', 'role:donor|admin'])->group(function () {
    Route::get('/donations', ...);
});
```

### In Controllers

Check user roles:
```php
// Check single role
if (auth()->user()->hasRole('admin')) {
    // Admin code
}

// Check multiple roles (OR)
if (auth()->user()->hasAnyRole(['admin', 'donor'])) {
    // Admin or Donor code
}

// Check all roles (AND)
if (auth()->user()->hasAllRoles(['admin', 'donor'])) {
    // Must have both roles
}
```

### In Blade Views

```blade
{{-- Check single role --}}
@if(auth()->user()->hasRole('admin'))
    <a href="{{ route('admin.dashboard') }}">Admin Panel</a>
@endif

{{-- Use @role directive --}}
@role('admin')
    <p>You are an admin</p>
@endrole

{{-- Check multiple roles --}}
@if(auth()->user()->hasAnyRole(['donor', 'admin']))
    <a href="{{ route('donations.create') }}">Make Donation</a>
@endif

{{-- Always wrap with @auth --}}
@auth
    @role('admin')
        <a href="/admin">Admin Dashboard</a>
    @endrole
@endauth
```

### Common Commands

```php
// Assign Role
$user->assignRole('admin');
$user->assignRole(['admin', 'donor']); // Multiple roles

// Remove Role
$user->removeRole('admin');

// Replace All Roles
$user->syncRoles(['donor']); // Removes old roles, assigns new ones

// Check Role
$user->hasRole('admin'); // Returns true/false
$user->hasAnyRole(['admin', 'donor']); // Returns true/false
$user->hasAllRoles(['admin', 'donor']); // Returns true/false
```

### Important Files

- **Model**: `app/Models/User.php` - Uses `HasRoles` trait
- **Seeder**: `database/seeders/RoleSeeder.php` - Creates roles
- **Middleware**: 
  - `app/Http/Middleware/EnsureRole.php` - Role verification
  - `app/Http/Middleware/RedirectByRole.php` - Role-based redirection
- **Config**: `config/permission.php` - Spatie Permission settings

---

## 📝 Spatie Activity Log (Audit)

This project uses **Spatie Laravel Activity Log** for audit logging. Important events (user actions, approvals, menu changes, etc.) are recorded in the `activity_log` table.

### Usage via AuditService

Use the `AuditService` for consistent logging across the app:

```php
// In a Service or Controller (inject AuditService)
$this->auditService->log('entity', 'action', [
    'entity_id' => 123,
    'extra_data' => 'value',
], $userId); // $userId optional, defaults to auth()->user()
```

### Current Audit Points

| Entity           | Actions                          | Location                    |
|-----------------|-----------------------------------|-----------------------------|
| `user`          | created, updated, deleted, deactivated, reactivated | `UserService`               |
| `account_approval` | approved, rejected             | `AccountApprovalController` |
| `menu_item`     | created, updated, deactivated     | `MenuItemController`        |
| `donation`      | confirmed                         | `DonationService` (when used) |

### Direct Spatie Usage (optional)

For one-off logs without AuditService:

```php
activity()->log('Something happened');
activity()->causedBy($user)->withProperties(['key' => 'value'])->log('custom.event');
```

### Important Files

- **Service**: `app/Services/AuditService.php` - Wrapper for consistent audit API
- **Table**: `activity_log` - Stores all audit entries
- **Docs**: [Spatie Activity Log](https://spatie.be/docs/laravel-activitylog)

---

## 🏛️ Architecture Best Practices

### Service Layer Pattern

Business logic should be in Services, not Controllers:

```php
// app/Services/DonationService.php
class DonationService
{
    public function __construct(
        private MyFatoorahService $myFatoorah,
        private AuditService $auditService
    ) {}
    
    public function initiateDonation(int $userId, float $amount): array
    {
        // Business logic here
    }
}

// app/Http/Controllers/Donor/DonationController.php
class DonationController extends Controller
{
    public function __construct(
        private DonationService $donationService
    ) {}
    
    public function store(StoreDonationRequest $request)
    {
        $result = $this->donationService->initiateDonation(
            auth()->id(),
            $request->validated()['amount']
        );
        
        return redirect($result['payment_url']);
    }
}
```

### Blade Layout Structure

The project uses **Lineone sidebar layout** (not Breeze top-nav):

```blade
{{-- Use x-app-layout for authenticated pages (sidebar + header) --}}
<x-app-layout title="{{ __('Page Title') }}">
    <div class="card p-6">
        <!-- Page content -->
    </div>
</x-app-layout>

{{-- Use x-guest-layout for login, register, etc. --}}
<x-guest-layout>
    <!-- Guest page content -->
</x-guest-layout>
```

Navigation is driven by `App\Support\SidebarPanel` and `App\Http\View\Composers\SidebarComposer` (see `app/Support/SidebarPanel.php` and `app/Http/View/Composers/SidebarComposer.php`) for the role-based sidebar menu.

### Route Organization

Role-specific routes are grouped in `routes/web.php` (prefix + middleware + closures/controllers). Authentication routes live in `routes/auth.php` and are included at the bottom of `routes/web.php` via `require __DIR__.'/auth.php';`. There are no separate `routes/admin.php` / `routes/donor.php` files.

Example (pattern only — see `routes/web.php` for the full tree):

```php
Route::middleware(array_merge($authMiddleware, ['account.approved', 'role:admin']))
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');
        // …
    });
```

---

## 🎯 Development Workflow

### Creating a New Module (Example: Donation)

1. **Create Model with Migration**
```bash
php artisan make:model Donation -m
```

2. **Edit Migration** (`database/migrations/xxxx_create_donations_table.php`)
```php
Schema::create('donations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->decimal('amount', 10, 2);
    $table->enum('status', ['pending', 'confirmed', 'failed']);
    $table->timestamps();
});
```

3. **Run Migration**
```bash
php artisan migrate
```

4. **Create Controller**
```bash
php artisan make:controller Donor/DonationController --resource
```

5. **Create Form Request**
```bash
php artisan make:request StoreDonationRequest
```

6. **Create Service** (manually)
```bash
# Create: app/Services/DonationService.php
```

7. **Create Views** (manually)
```bash
# Create: resources/views/donor/donations/
#   - index.blade.php
#   - create.blade.php
#   - show.blade.php
```

8. **Add Routes** inside the appropriate group in `routes/web.php` (for example the `donor` prefix / middleware group), not a separate `routes/donor.php` file:
```php
Route::resource('donations', DonationController::class);
```

9. **Clear Cache**
```bash
php artisan optimize:clear
```

---

## 🧪 Testing

### PHPUnit

Automated tests live under `tests/Feature` and `tests/Unit`. Run:

```bash
php artisan test
# or
composer test
```

### Playwright (E2E)

Browser tests are in `tests/e2e/` (`*.spec.js`). Config files:

| File | Purpose |
|------|---------|
| `config/playwright/playwright.config.js` | Default: starts Laravel (`php artisan serve` on port **8001**) and Vite |
| `config/playwright/playwright.php-only.config.js` | Laravel only (no Vite) — lighter runs |

```bash
npm install
npx playwright install chromium   # first time / new machine
npm run test:e2e                    # full stack
npm run test:e2e:php-only          # PHP server only
npm run test:e2e:ui                # Playwright UI mode
```

Extended notes (ports, troubleshooting): see `docs/playwright.md` when that file is available in your doc set.

---

## 🔍 Troubleshooting

### Vite Not Working
```bash
# Make sure Vite is running
npm run dev

# Or build for production
npm run build
```

### Tailwind Classes Not Working
```bash
# Rebuild assets
npm run build

# Clear Laravel cache
php artisan optimize:clear
```

### Routes Not Found
```bash
# Clear route cache
php artisan route:clear
php artisan optimize:clear

# List all routes
php artisan route:list
```

### Permission Issues
```bash
# Clear cache after role changes
php artisan optimize:clear

# Re-run migrations
php artisan migrate:fresh --seed
```

---

## ⚠️ Important Notes

1. **Always use `@auth`** in Blade before checking roles
2. **Run `npm run dev`** during development (keeps Vite running)
3. **Run `npm run build`** before production deployment
4. **Clear cache** after config/routes changes: `php artisan optimize:clear`
5. **Service Layer** for business logic, Controllers for HTTP handling
6. **Form Requests** for validation, not in Controllers
7. **Blade-only** - No Vue, React, or Livewire
8. **Run migrations** before using roles
9. **Run RoleSeeder** to create basic roles

---

## 💳 Recipient Weekly Allowance Limit

- **Config key**: `recipient.weekly_allowance_limit` (default: `400`)
- **Where to change it**: `config/provider.php` → `recipient.weekly_allowance_limit`
- **Where it’s used**: `App\Services\RecipientAllowanceService` reads it via `config('recipient.weekly_allowance_limit', 400)`
- **After changing**: clear cache with `php artisan optimize:clear`

---

## 📦 Installed Packages

### PHP Packages (Composer)
- `laravel/framework` ^12.0 — Core Laravel
- `laravel/sanctum` ^4.3 — API authentication
- `spatie/laravel-permission` ^6.24 — Roles & permissions
- `spatie/laravel-activitylog` ^4.11 — Audit logging
- `myfatoorah/library` — Payments
- `phpoffice/phpspreadsheet` — Excel exports / reports
- `simplesoftwareio/simple-qrcode` — QR flows
- `laravel-lang/common` (and related) — Translations
- `taqnyat/php` — SMS
- Requires PHP extension **bcmath** (wallet / allocation math)

### NPM Packages
- `tailwindcss` ^4.1.18 — CSS framework
- `@tailwindcss/vite` ^4.1.18 — Tailwind Vite plugin
- `alpinejs` ^3.15.3 — JavaScript
- `@playwright/test` — E2E tests
- `simplebar`, `apexcharts`, `tom-select`, etc. — Lineone / UI dependencies
- `vite` ^7.0.7 — Build tool

--- 
### External

- [Laravel Documentation](https://laravel.com/docs)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Lineone Template](https://lineone.pixelcave.com) - UI reference
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [Spatie Laravel Activity Log](https://spatie.be/docs/laravel-activitylog)
- [Alpine.js Documentation](https://alpinejs.dev)
- [Playwright Documentation](https://playwright.dev)

---
