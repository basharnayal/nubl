# NUBL - Neighborhood-Based Digital Food Assistance Platform

A digital platform for neighborhood-based food assistance (sadaqah) that connects donors, beneficiaries, and providers in a dignified, private, and transparent manner.

---

## 🛠️ Technology Stack

- **Backend**: Laravel 12 (PHP 8.2+) latest version
- **Frontend**: Blade Templates 
- **CSS Framework**: Tailwind CSS v4
- **UI Components**: Lineone (Alpine.js + Tailwind)
- **JavaScript**: Alpine.js
- **Build Tool**: Vite
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
copy .env.example .env
php artisan key:generate

# Edit .env file with your database credentials and MyFatoorah API keys

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
| List Routes | `php artisan route:list` |
| Tinker | `php artisan tinker` |
| Queue Worker | `php artisan queue:work` |

---

## 🏗️ Project Structure

### Folder Structure

```
nubl/
├── app/
│   ├── Enums/                    # Status Enums
│   ├── Http/
│   │   ├── Controllers/          # Controllers (organized by role)
│   │   │   ├── Admin/
│   │   │   ├── Donor/
│   │   │   ├── recipient/
│   │   │   └── Provider/
│   │   ├── Middleware/           # Custom Middleware
│   │   ├── Requests/             # Form Requests (Validation)
│   │   └── Services/             # Business Logic Layer
│   ├── Models/                   # Eloquent Models
│   ├── Providers/                # Service Providers
│   └── View/Components/          # Blade Component Classes
│
├── database/
│   ├── migrations/               # Database Migrations
│   └── seeders/                  # Database Seeders
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
└── routes/
    ├── web.php                   # Web Routes
    ├── admin.php                 # Admin Routes (to be created)
    ├── donor.php                 # Donor Routes (to be created)
    ├── recipient.php             # Recipient Routes (to be created)
    └── provider.php              # Provider Routes (to be created)
```

### Architecture Pattern

- **MVC Pattern**: Model-View-Controller
- **Service Layer**: Business logic in `app/Http/Services/`
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

- **Service**: `app/Http/Services/AuditService.php` - Wrapper for consistent audit API
- **Table**: `activity_log` - Stores all audit entries
- **Docs**: [Spatie Activity Log](https://spatie.be/docs/laravel-activitylog)

---

## 🏛️ Architecture Best Practices

### Service Layer Pattern

Business logic should be in Services, not Controllers:

```php
// app/Http/Services/DonationService.php
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

Navigation is driven by `App\Support\SidebarPanel` and `SidebarComposer` (`app/Support/SidebarPanel.php`, role-based sidebar menu).

### Route Organization

```php
// routes/web.php
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')
    ->group(base_path('routes/admin.php'));

Route::middleware(['auth', 'role:donor'])->prefix('donor')->name('donor.')
    ->group(base_path('routes/donor.php'));
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
# Create: app/Http/Services/DonationService.php
```

7. **Create Views** (manually)
```bash
# Create: resources/views/donor/donations/
#   - index.blade.php
#   - create.blade.php
#   - show.blade.php
```

8. **Add Routes**
```php
// routes/donor.php
Route::resource('donations', DonationController::class);
```

9. **Clear Cache**
```bash
php artisan optimize:clear
```

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
- `laravel/framework` ^12.0 - Core Laravel
- `laravel/sanctum` ^4.3 - API Authentication
- `spatie/laravel-permission` ^6.24 - Roles & Permissions
- `spatie/laravel-activitylog` ^4.11 - Audit Logging

### NPM Packages
- `tailwindcss` ^4.1.18 - CSS Framework
- `@tailwindcss/vite` ^4.1.18 - Tailwind Vite Plugin
- `alpinejs` ^3.15.3 - JavaScript Framework
- `simplebar`, `apexcharts`, `tom-select`, etc. - Lineone dependencies
- `vite` ^7.0.7 - Build Tool

---
### External

- [Laravel Documentation](https://laravel.com/docs)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Lineone Template](https://lineone.pixelcave.com) - UI reference
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [Spatie Laravel Activity Log](https://spatie.be/docs/laravel-activitylog)
- [Alpine.js Documentation](https://alpinejs.dev)

---
