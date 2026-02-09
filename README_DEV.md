# NUBL - Development Documentation

> **⚠️ IMPORTANT: This is the CENTRAL documentation file for all development-related documentation.**
> 
> **All developers and AI assistants MUST add any new documentation to this file, NOT create separate `.md` files.**
> 
> **Follow the same conventions and structure. Keep everything organized and in English.**

---

## Table of Contents

- [Git Commit Conventions](#git-commit-conventions)
- [Email Configuration](#email-configuration)
- [Roles & Permissions](#roles--permissions)
  - [Setup](#setup)
  - [Available Roles](#available-roles)
  - [Using Roles in Routes](#using-roles-in-routes)
  - [Using Roles in Controllers](#using-roles-in-controllers)
  - [Using Roles in Views](#using-roles-in-views)
  - [Managing Roles Programmatically](#managing-roles-programmatically)
  - [Permissions (Future Reference)](#permissions-future-reference)
- [Additional Documentation](#additional-documentation)

---

## Git Commit Conventions

Simple commit message rules for clean Git history and easy code reviews.

### Format

```
<type>(<scope>): <summary> [FR-#]
```

**Example:**
```
feat(auth): add RBAC middleware [FR-1.5]
```

**Rules:**
- Use present tense (add, fix, NOT added/fixed)
- Keep summary ≤ 72 characters
- One commit = one change
- Add `[FR-#]` or `[NFR-#]` if related to requirements

### Types (Remember: F F R D T C)

| Type | Use When |
|------|----------|
| **feat** | New feature |
| **fix** | Bug fix |
| **refactor** | Code restructure (no behavior change) |
| **docs** | Documentation only |
| **test** | Tests only |
| **chore** | Setup/config/dependencies |

### Examples

```
feat(donations): create donation form [FR-2.1]
fix(auth): prevent unauthorized access [FR-1.5]
refactor(services): extract donation logic
docs(readme): add Spatie setup guide
test(auth): add role tests [FR-1.5]
chore(deps): install spatie/laravel-permission
```

### Common Scopes

- `auth`, `roles`, `users`
- `donations`, `requests`, `qr`
- `routes`, `views`, `ui`
- `db`, `migrations`, `seeders`
- `config`, `core`

### Optional Body

For complex changes, add details:

```
feat(auth): implement RBAC [FR-1.5]

- Added Spatie roles
- Protected routes with middleware
- Added role-based redirects
```

### Rules

**DO:**
- ✅ Write clear messages
- ✅ Keep commits small
- ✅ Reference FR/NFR when relevant
- ✅ One logical change per commit

**DON'T:**
- ❌ Vague messages ("update stuff")
- ❌ Mix unrelated changes
- ❌ Skip requirement mapping

### Quick Reference

**Format:** `type(scope): summary [FR-#]`  
**Types:** feat, fix, refactor, docs, test, chore  
**Always:** Present tense, clear, one change

**That's it! Keep it simple and consistent.** 🚀

---

## Email Configuration

### Mailtrap Setup (Development)

Mailtrap is the recommended email service for local development because:
- ✅ Free for development/testing
- ✅ Beautiful web interface to view emails
- ✅ No local installation required
- ✅ Easy to set up

#### Setup Steps

1. **Create Mailtrap Account:**
   - Go to: https://mailtrap.io
   - Sign up for a free account
   - After logging in, navigate to: **Email Testing** → **Sandboxes** → **My Sandbox**

2. **Get SMTP Credentials:**
   - In your Sandbox, click on **SMTP** tab
   - You'll see:
     - Host: `sandbox.smtp.mailtrap.io`
     - Port: `2525` (recommended for Laravel)
     - Username: (your unique username)
     - Password: (your unique password - click eye icon to reveal)

3. **Update `.env` File:**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=sandbox.smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=your-mailtrap-username
   MAIL_PASSWORD=your-mailtrap-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS="noreply@nubl.com"
   MAIL_FROM_NAME="${APP_NAME}"
   ```

4. **Clear Config Cache:**
   ```bash
   php artisan config:clear
   ```

5. **View Emails:**
   - After sending any email from the application, go back to Mailtrap Dashboard
   - Navigate to: **Email Testing** → **Sandboxes** → **My Sandbox**
   - All sent emails will appear there
   - You can open and read them directly

#### Testing Email Setup

After updating `.env`, test the email configuration:

```bash
php artisan tinker
```

Then in Tinker:
```php
Mail::raw('Test email', function ($message) {
    $message->to('test@example.com')
            ->subject('Test Email');
});
```

Check your Mailtrap inbox to see the test email.

---

## Roles & Permissions

This project uses **Spatie Laravel Permission** for role-based access control (RBAC).

### Setup

#### 1. Run Seeders

```bash
# Run all seeders (creates permissions, roles, and assigns permissions to roles)
php artisan db:seed

# Or run individually
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder
```

#### 2. Clear Cache

```bash
php artisan optimize:clear
```

### Available Roles

The project has 4 main roles:

- **`admin`** - System administrator (has access to everything)
- **`donor`** - Donor (Gracious Neighbor) - can make donations
- **`recipient`** - Beneficiary (Neighbor) - can request items
- **`provider`** - Provider (Local supermarkets/restaurants) - can fulfill requests

### Using Roles in Routes

Protect routes based on user roles:

```php
// Single role
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
});

// Multiple roles (user needs ANY of these roles)
Route::middleware(['auth', 'role:donor|recipient'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Role-specific routes
Route::middleware(['auth', 'role:donor'])->prefix('donor')->group(function () {
    Route::get('/dashboard', [DonorController::class, 'dashboard']);
});

Route::middleware(['auth', 'role:recipient'])->prefix('recipient')->group(function () {
    Route::get('/dashboard', [RecipientController::class, 'dashboard']);
});

Route::middleware(['auth', 'role:provider'])->prefix('provider')->group(function () {
    Route::get('/dashboard', [ProviderController::class, 'dashboard']);
});
```

### Using Roles in Controllers

Check user roles in controller methods:

```php
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Method 1: Check if user has role
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        
        // Method 2: Check multiple roles
        if ($user->hasAnyRole(['donor', 'recipient'])) {
            // User is either donor or recipient
        }
        
        // Method 3: Abort if user doesn't have role
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized - Admin access required');
        }
        
        // Your code here
    }
}
```

### Using Roles in Views

Show/hide content based on user roles:

```blade
{{-- Method 1: @role directive (recommended) --}}
@role('admin')
    <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
@endrole

@role('donor')
    <a href="{{ route('donor.dashboard') }}">Donor Dashboard</a>
@endrole

{{-- Method 2: @hasrole directive --}}
@hasrole('admin')
    <div>Admin content</div>
@else
    <div>Regular user content</div>
@endhasrole

{{-- Method 3: Check multiple roles --}}
@hasanyrole('admin|donor')
    <div>Admin or Donor content</div>
@endhasanyrole

{{-- Method 4: Using hasRole() method --}}
@if(auth()->user()->hasRole('admin'))
    <button>Admin Button</button>
@endif

{{-- Method 5: Check if user has any of multiple roles --}}
@if(auth()->user()->hasAnyRole(['donor', 'recipient']))
    <p>You are a donor or recipient</p>
@endif
```

### Managing Roles Programmatically

#### Assign Role to User

```php
$user = User::find(1);
$user->assignRole('donor');

// Assign multiple roles
$user->assignRole(['donor', 'recipient']);
```

#### Remove Role from User

```php
$user->removeRole('donor');

// Remove multiple roles
$user->removeRole(['donor', 'recipient']);
```

#### Sync Roles (Replace all roles)

```php
// This will remove all existing roles and assign only 'admin'
$user->syncRoles(['admin']);
```

#### Check User Roles

```php
$user = User::find(1);

// Check if user has role
$user->hasRole('admin'); // true/false

// Check if user has any of the roles
$user->hasAnyRole(['admin', 'donor']); // true/false

// Check if user has all roles
$user->hasAllRoles(['admin', 'donor']); // true/false

// Get all user roles
$user->roles; // Collection of Role models
$user->roles->pluck('name'); // ['admin', 'donor']
```

### Complete Example: Role-Based Dashboard Redirect

#### In Middleware (RedirectByRole)

```php
public function handle(Request $request, Closure $next)
{
    if (!auth()->check()) {
        return $next($request);
    }
    
    $user = auth()->user();
    
    // Redirect based on role
    if ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }
    
    if ($user->hasRole('donor')) {
        return redirect()->route('donor.dashboard');
    }
    
    if ($user->hasRole('recipient')) {
        return redirect()->route('recipient.dashboard');
    }
    
    if ($user->hasRole('provider')) {
        return redirect()->route('provider.dashboard');
    }
    
    return $next($request);
}
```

#### In View (Navigation)

```blade
<nav>
    @role('admin')
        <a href="{{ route('admin.dashboard') }}">Admin</a>
    @endrole
    
    @role('donor')
        <a href="{{ route('donor.dashboard') }}">Donor</a>
    @endrole
    
    @role('recipient')
        <a href="{{ route('recipient.dashboard') }}">Recipient</a>
    @endrole
    
    @role('provider')
        <a href="{{ route('provider.dashboard') }}">Provider</a>
    @endrole
</nav>
```

### Quick Commands

```bash
# Open Tinker
php artisan tinker

# Assign role to user
$user = User::find(1);
$user->assignRole('admin');

# Check user role
$user->hasRole('admin'); // true/false

# Get all roles
$user->roles->pluck('name');

# Remove role
$user->removeRole('admin');
```

### Important Notes

- **Admin role** automatically has access to everything
- Roles are cached for performance
- Always clear cache after role changes: `php artisan optimize:clear`
- Users can have multiple roles
- Use `syncRoles()` to replace all roles at once

---

## Permissions (Future Reference)

> **Note:** Permissions are defined in the system but not fully implemented yet. This section is for future reference.

### Permission Hierarchy

```
User
  └── Role (admin, donor, recipient, provider)
       └── Permissions (donations.create, requests.view, etc.)
```

### Available Permissions

**Admin Permissions:**
- `accounts.approve`, `requests.review`, `requests.approve`, `requests.reject`
- `users.manage`, `users.assign.roles`, `roles.manage`, `permissions.manage`
- `reports.export_csv`, `reports.export_pdf`, `allowances.configure`
- And more...

**Donor Permissions:**
- `donations.process`, `dashboard.donor.view_stats`

**Recipient Permissions:**
- `requests.submit`

**Provider Permissions:**
- `qr.redeem`, `fulfillment_proof.upload`, `requests.adopt`
- `provider.capacity.toggle`, `provider.pickup_notes_and_hours.update`

### Using Permissions (When Implemented)

```php
// In Routes
Route::middleware(['auth', 'permission:donations.create'])->group(function () {
    // Routes here
});

// In Controllers
if (!auth()->user()->can('donations.create')) {
    abort(403);
}

// In Views
@can('donations.create')
    <button>Create Donation</button>
@endcan
```

---

## Additional Documentation

> **📝 Add new documentation sections below this line.**
> 
> **Format: Use clear headings, code blocks, and organized structure.**
> **Language: English only.**
> **Conventions: Follow the same style as existing sections.**

---
