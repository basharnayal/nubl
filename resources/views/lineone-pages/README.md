# Lineone Reference Pages

This folder contains **132 Lineone template pages** copied for reference. Use them when you need a ready-made page layout or component.

> **الملفات الداعمة:** انظر `resources/lineone-reference/README.md` للمزايا البرمجية (JS, CSS, routes).

## How to Use

1. **Copy** the blade file you need from `lineone-pages/` to your target location (e.g. `admin/`, `provider/`, `recipient/`, etc.)
2. **Adapt** the page for NUBL:
   - Replace `<main class="main-content w-full pb-8">` with `<div class="w-full pb-8">` if the page goes inside `x-app-layout` (to avoid nested main)
   - Remove `px-[var(--margin-x)]` from grid divs if the parent layout already provides padding
   - Update asset paths if needed (`images/`, `images/awards/`, etc.)
3. **Add route** in `routes/web.php` pointing to the new view

## Page Categories

| Category | Files | Description |
|----------|-------|-------------|
| **Dashboards** | `dashboards-*.blade.php` | CRM, Banking, Crypto, Doctor, Education, etc. |
| **Apps** | `apps-*.blade.php` | AI Chat, Kanban, Mail, Todo, POS, File Manager, etc. |
| **Components** | `components-*.blade.php` | Accordion, Modal, Table, Timeline, ApexChart, etc. |
| **Elements** | `elements-*.blade.php` | Alert, Avatar, Badge, Button, Card, Progress, etc. |
| **Forms** | `forms-*.blade.php` | Input, Select, Datepicker, Validation, Upload, etc. |
| **Layouts** | `layouts-*.blade.php` | Sign-in, Sign-up, Invoice, Error pages, Blog, etc. |

## Example

To use the CRM dashboard in admin:

```php
// routes/web.php
Route::get('/admin/dashboard', fn() => view('admin.dashboard'))->name('admin.dashboard');
```

Then copy `dashboards-crm-analytics.blade.php` → `admin/dashboard.blade.php` and adapt.
