# 🔐 Permissions & Roles Guide - NUBL Project

Complete guide for using Spatie Laravel Permission with Roles and Permissions.

---

## 📋 Setup Instructions

### 1. Run Seeders

```bash
# Run all seeders (creates permissions, roles, and assigns permissions to roles)
php artisan db:seed

# Or run individually
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder
```

### 2. Clear Cache

```bash
php artisan optimize:clear
```

---

## 🎯 Available Permissions

### Donor Permissions
- `donations.create` - Create new donation
- `donations.view` - View own donations
- `donations.view.all` - View all donations (admin)
- `donations.cancel` - Cancel pending donation

### Recipient Permissions
- `requests.create` - Create new request
- `requests.view` - View own requests
- `requests.view.all` - View all requests (admin)
- `requests.edit` - Edit own request
- `requests.cancel` - Cancel own request

### Provider Permissions
- `qr.scan` - Scan QR code
- `qr.redeem` - Redeem QR code
- `requests.view` - View requests
- `requests.adopt` - Adopt a request as donation
- `requests.fulfill` - Mark request as fulfilled
- `proof.upload` - Upload proof of fulfillment

### Admin Permissions
- `requests.approve` - Approve/reject requests
- `requests.manage` - Manage all requests
- `users.manage` - Manage users
- `users.assign.roles` - Assign roles to users
- `roles.manage` - Manage roles
- `permissions.manage` - Manage permissions
- `reports.view` - View reports
- `reports.export` - Export reports
- `audit.view` - View audit logs
- `settings.manage` - Manage system settings

---

## 💻 Usage Examples

### In Routes

```php
// Single permission
Route::middleware(['auth', 'permission:donations.create'])->group(function () {
    Route::get('/donations/create', [DonationController::class, 'create']);
    Route::post('/donations', [DonationController::class, 'store']);
});

// Multiple permissions (user needs ALL)
Route::middleware(['auth', 'permission:requests.approve|requests.manage'])->group(function () {
    Route::get('/admin/requests', [AdminController::class, 'index']);
});
```

### In Controllers

```php
// Method 1: Using authorize()
public function create()
{
    $this->authorize('donations.create');
    return view('donations.create');
}

// Method 2: Using can()
public function store(Request $request)
{
    if (!auth()->user()->can('donations.create')) {
        abort(403, 'Unauthorized');
    }
    
    // Your code here
}

// Method 3: Using Gate facade
use Illuminate\Support\Facades\Gate;

public function destroy($id)
{
    if (!Gate::allows('donations.delete')) {
        abort(403);
    }
    
    // Your code here
}
```

### In Blade Views

```blade
{{-- Method 1: @can directive --}}
@can('donations.create')
    <a href="{{ route('donations.create') }}" class="btn btn-primary">
        إضافة تبرع جديد
    </a>
@endcan

{{-- Method 2: @permission directive --}}
@permission('donations.create')
    <button>إضافة تبرع</button>
@endpermission

{{-- Method 3: Using can() method --}}
@if(auth()->user()->can('donations.create'))
    <a href="{{ route('donations.create') }}">إضافة تبرع</a>
@endif

{{-- Method 4: Check multiple permissions --}}
@canany(['donations.create', 'donations.edit'])
    <div>You can create or edit donations</div>
@endcanany

{{-- Method 5: Check all permissions --}}
@canall(['donations.create', 'donations.view'])
    <div>You can create AND view donations</div>
@endcanall
```

---

## 🔧 Managing Permissions Programmatically

### Assign Permission to User

```php
$user = User::find(1);
$user->givePermissionTo('donations.create');

// Multiple permissions
$user->givePermissionTo(['donations.create', 'donations.view']);
```

### Assign Permission to Role

```php
$role = Role::findByName('donor');
$role->givePermissionTo('donations.create');

// Multiple permissions
$role->givePermissionTo(['donations.create', 'donations.view']);
```

### Remove Permission from User

```php
$user->revokePermissionTo('donations.create');
```

### Remove Permission from Role

```php
$role->revokePermissionTo('donations.create');
```

### Sync Permissions (Replace all)

```php
// User
$user->syncPermissions(['donations.create', 'donations.view']);

// Role
$role->syncPermissions(['donations.create', 'donations.view']);
```

### Check Permissions

```php
// Check if user has permission
$user->can('donations.create'); // true/false
$user->hasPermissionTo('donations.create'); // true/false

// Check if user has any of the permissions
$user->hasAnyPermission(['donations.create', 'donations.edit']); // true/false

// Check if user has all permissions
$user->hasAllPermissions(['donations.create', 'donations.view']); // true/false

// Check via role
$user->hasPermissionTo('donations.create'); // Checks both direct and via role
```

---

## 🎨 Complete Example: Donation Button

### 1. Permission exists: `donations.create`
### 2. Role has permission: `donor` role has `donations.create`
### 3. User has role: User is assigned `donor` role

### In View:
```blade
@can('donations.create')
    <a href="{{ route('donations.create') }}" class="btn btn-primary">
        إضافة تبرع جديد
    </a>
@else
    <p class="text-gray-500">You don't have permission to create donations</p>
@endcan
```

### In Route:
```php
Route::get('/donations/create', [DonationController::class, 'create'])
    ->middleware(['auth', 'permission:donations.create']);
```

### In Controller:
```php
public function create()
{
    $this->authorize('donations.create');
    return view('donations.create');
}
```

---

## 📊 Permission Hierarchy

```
User
  └── Role (donor, recipient, provider, admin)
       └── Permissions (donations.create, requests.view, etc.)
```

**How it works:**
- User gets permissions through their role
- Admin role has ALL permissions
- You can also assign permissions directly to users (bypasses role)

---

## ✅ Best Practices

1. **Use Permissions for Actions**: Use permissions for specific actions (create, view, delete)
2. **Use Roles for Groups**: Use roles to group users (donor, admin)
3. **Assign Permissions to Roles**: Don't assign permissions directly to users (unless needed)
4. **Check in Multiple Layers**: Check permissions in Routes, Controllers, AND Views
5. **Clear Cache**: Always clear cache after permission changes

---

## 🚀 Quick Commands

```bash
# Create permission
php artisan tinker
Permission::create(['name' => 'new.permission']);

# Assign permission to role
$role = Role::findByName('donor');
$role->givePermissionTo('new.permission');

# Assign permission to user
$user = User::find(1);
$user->givePermissionTo('new.permission');

# Check permission
$user->can('new.permission');
```

---

## 📝 Notes

- Permissions are cached for 24 hours by default
- Cache is automatically cleared when permissions/roles change
- Use `syncPermissions()` to replace all permissions
- Admin role automatically has all permissions
- You can check permissions via role OR direct assignment

---

**Last Updated:** 2026-02-02


**$$$$$$$$$$$$$$$$$$$$$$$$FILE 2$$$$$$$$$$$$$$$$$$$$$$$$$$$**
# 🔐 Permissions Examples - Practical Usage

Real-world examples for using Permissions in NUBL project.

---

## 📝 Example 1: Donation Creation (Donor Only)

### Permission: `donations.create`
### Role: `donor`

### Route:
```php
Route::middleware(['auth', 'permission:donations.create'])->group(function () {
    Route::get('/donations/create', [DonationController::class, 'create'])->name('donations.create');
    Route::post('/donations', [DonationController::class, 'store'])->name('donations.store');
});
```

### Controller:
```php
public function create()
{
    $this->authorize('donations.create');
    return view('donor.donations.create');
}

public function store(Request $request)
{
    $this->authorize('donations.create');
    // Create donation logic
}
```

### View:
```blade
@can('donations.create')
    <a href="{{ route('donations.create') }}" class="btn btn-primary">
        إضافة تبرع جديد
    </a>
@else
    <p class="text-muted">You need donor role to create donations</p>
@endcan
```

---

## 📝 Example 2: Request Approval (Admin Only)

### Permission: `requests.approve`
### Role: `admin`

### Route:
```php
Route::middleware(['auth', 'permission:requests.approve'])->group(function () {
    Route::post('/admin/requests/{id}/approve', [AdminController::class, 'approve'])->name('admin.requests.approve');
    Route::post('/admin/requests/{id}/reject', [AdminController::class, 'reject'])->name('admin.requests.reject');
});
```

### Controller:
```php
public function approve($id)
{
    $this->authorize('requests.approve');
    
    $request = BeneficiaryRequest::findOrFail($id);
    $request->update(['status' => 'approved']);
    
    return redirect()->back()->with('success', 'Request approved');
}
```

### View:
```blade
@can('requests.approve')
    <form action="{{ route('admin.requests.approve', $request->id) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-success">Approve</button>
    </form>
@endcan
```

---

## 📝 Example 3: QR Code Scanning (Provider Only)

### Permission: `qr.scan`
### Role: `provider`

### Route:
```php
Route::middleware(['auth', 'permission:qr.scan'])->group(function () {
    Route::get('/provider/qr/scan', [QRController::class, 'scan'])->name('provider.qr.scan');
    Route::post('/provider/qr/redeem', [QRController::class, 'redeem'])->name('provider.qr.redeem');
});
```

### Controller:
```php
public function scan()
{
    $this->authorize('qr.scan');
    return view('provider.qr.scan');
}

public function redeem(Request $request)
{
    $this->authorize('qr.redeem');
    // Redeem QR code logic
}
```

### View:
```blade
@can('qr.scan')
    <div class="qr-scanner">
        <video id="scanner"></video>
        <button onclick="scanQR()">Scan QR Code</button>
    </div>
@endcan
```

---

## 📝 Example 4: Request Creation (Recipient Only)

### Permission: `requests.create`
### Role: `recipient`

### Route:
```php
Route::middleware(['auth', 'permission:requests.create'])->group(function () {
    Route::get('/requests/create', [RequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
});
```

### Controller:
```php
public function create()
{
    if (!auth()->user()->can('requests.create')) {
        abort(403, 'Only recipients can create requests');
    }
    
    $providers = Provider::where('is_active', true)->get();
    return view('recipient.requests.create', compact('providers'));
}
```

### View:
```blade
@can('requests.create')
    <a href="{{ route('requests.create') }}" class="btn btn-primary">
        طلب جديد
    </a>
@else
    <div class="alert alert-info">
        You need to be a recipient to create requests
    </div>
@endcan
```

---

## 📝 Example 5: Multiple Permissions Check

### Check if user can create OR edit donations

### View:
```blade
@canany(['donations.create', 'donations.edit'])
    <div class="donation-actions">
        @can('donations.create')
            <a href="{{ route('donations.create') }}">Create</a>
        @endcan
        
        @can('donations.edit')
            <a href="{{ route('donations.edit', $donation->id) }}">Edit</a>
        @endcan
    </div>
@endcanany
```

### Controller:
```php
public function update(Request $request, $id)
{
    // User needs BOTH permissions
    if (!auth()->user()->hasAllPermissions(['donations.view', 'donations.edit'])) {
        abort(403);
    }
    
    // Update logic
}
```

---

## 📝 Example 6: Admin Dashboard (Multiple Permissions)

### Admin needs multiple permissions

### Route:
```php
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    // Admin can access all these routes
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'users'])->middleware('permission:users.manage');
    Route::get('/reports', [AdminController::class, 'reports'])->middleware('permission:reports.view');
});
```

### View:
```blade
@role('admin')
    <div class="admin-panel">
        @can('users.manage')
            <a href="{{ route('admin.users') }}">Manage Users</a>
        @endcan
        
        @can('reports.view')
            <a href="{{ route('admin.reports') }}">View Reports</a>
        @endcan
        
        @can('audit.view')
            <a href="{{ route('admin.audit') }}">Audit Logs</a>
        @endcan
    </div>
@endrole
```

---

## 📝 Example 7: Conditional Button Display

### Show button only if user has permission

```blade
<div class="action-buttons">
    @can('donations.create')
        <button class="btn btn-primary" onclick="createDonation()">
            إضافة تبرع
        </button>
    @endcan
    
    @can('requests.create')
        <button class="btn btn-success" onclick="createRequest()">
            طلب جديد
        </button>
    @endcan
    
    @can('qr.scan')
        <button class="btn btn-info" onclick="scanQR()">
            مسح QR
        </button>
    @endcan
</div>
```

---

## 📝 Example 8: API-like Permission Check

### In Controller with JSON response

```php
public function apiCreate(Request $request)
{
    if (!auth()->user()->can('donations.create')) {
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to create donations'
        ], 403);
    }
    
    // Create donation
    return response()->json(['success' => true]);
}
```

---

## 🔄 How Permissions Flow

```
1. User logs in
2. User has role (e.g., 'donor')
3. Role has permissions (e.g., 'donations.create')
4. User inherits permissions from role
5. Check permission in Route/Controller/View
6. Access granted or denied
```

---

## ✅ Testing Permissions

### In Tinker:
```php
php artisan tinker

// Check if user has permission
$user = User::find(1);
$user->can('donations.create'); // true/false

// Check via role
$user->hasRole('donor'); // true/false
$user->hasPermissionTo('donations.create'); // true/false

// List all permissions for user
$user->getAllPermissions();

// List all permissions for role
$role = Role::findByName('donor');
$role->permissions;
```

---

**Remember:** Always check permissions in Routes, Controllers, AND Views for complete security!
=========================================
# 🔐 Permissions Mapping to SRS Requirements

Mapping of all permissions to their corresponding Functional Requirements (FR) from the SRS.

---

## 📋 Admin Permissions

| Permission | FR Reference | Description |
|------------|--------------|-------------|
| `accounts.approve` | FR 1.4 | Approve accounts (Beneficiary/Provider) |
| `requests.review` | FR 7.1 | Review requests |
| `requests.approve` | FR 7.1 | Approve requests |
| `requests.reject` | FR 7.1 | Reject requests |
| `qr.configure_ttl` | FR 8.3 | Configure QR TTL |
| `users.create` | FR 12.1 | Create users (CRUD) |
| `users.read` | FR 12.1 | Read users (CRUD) |
| `users.update` | FR 12.1 | Update users (CRUD) |
| `users.delete` | FR 12.1 | Delete users (CRUD) |
| `users.manage` | Admin Panel | General user management |
| `users.assign.roles` | Admin Panel | Assign roles to users |
| `users.deactivate` | FR 20.1 | Deactivate accounts |
| `users.reactivate` | FR 20.1 | Reactivate accounts |
| `funds.create` | FR 12.1 | Create funds (CRUD) |
| `funds.read` | FR 12.1 | Read funds (CRUD) |
| `funds.update` | FR 12.1 | Update funds (CRUD) |
| `funds.delete` | FR 12.1 | Delete funds (CRUD) |
| `policies.create` | FR 12.1 | Create policies (CRUD) |
| `policies.read` | FR 12.1 | Read policies (CRUD) |
| `policies.update` | FR 12.1 | Update policies (CRUD) |
| `policies.delete` | FR 12.1 | Delete policies (CRUD) |
| `reports.export_csv` | FR 15.1 | Export reports as CSV |
| `reports.export_pdf` | FR 15.1 | Export reports as PDF |
| `allowances.configure` | FR 17.1 | Configure system-wide allowance values |
| `allocation.pause_global` | FR 24.1 | Pause allocation engine globally |
| `allocation.pause_per_provider` | FR 24.1 | Pause allocation engine per provider |
| `roles.manage` | Admin Panel | Manage roles (for admin panel) |
| `permissions.manage` | Admin Panel | Manage permissions (for admin panel) |

**Total Admin Permissions:** 28

---

## 💝 Donor Permissions

| Permission | FR Reference | Description |
|------------|--------------|-------------|
| `donations.process` | FR 3.1 | Process donations via MyFatoorah |
| `dashboard.donor.view_stats` | FR 4.1 | View aggregated donor dashboard statistics |

**Total Donor Permissions:** 2

---

## 🙏 Beneficiary (Recipient) Permissions

| Permission | FR Reference | Description |
|------------|--------------|-------------|
| `requests.submit` | FR 5.1 | Submit digital item requests |

**Total Recipient Permissions:** 1

---

## 🏪 Provider Permissions

| Permission | FR Reference | Description |
|------------|--------------|-------------|
| `qr.redeem` | FR 9.1 | Redeem QR codes (scan/manual) |
| `fulfillment_proof.upload` | FR 10.1 | Upload proof of fulfillment |
| `requests.adopt` | FR 21.1 | Adopt pending request and fulfill as donation |
| `provider.capacity.toggle` | FR 23.1 | Toggle service capacity (ON/OFF) |
| `provider.pickup_notes_and_hours.update` | FR 23.2 | Set pickup notes and operating hours |

**Total Provider Permissions:** 5

---

## 📊 Summary

- **Total Permissions:** 36
- **Admin:** 28 permissions
- **Donor:** 2 permissions
- **Recipient:** 1 permission
- **Provider:** 5 permissions

---

## ⚠️ Notes

1. **Admin Panel Permissions** (`roles.manage`, `permissions.manage`, `users.manage`, `users.assign.roles`) are required for the admin management interfaces we created, even though they're not explicitly mentioned in SRS.

2. **System Internal Requirements** (FR 1.1, 1.2, 1.3, 18.1, 18.2) don't require permissions as they are system-level behaviors, not user actions.

3. **FR 1.5 (RBAC)** is the framework itself (Roles + route protection), not a specific permission.

4. **Admin role** automatically gets ALL permissions when seeded.

---

**Last Updated:** 2026-02-02
