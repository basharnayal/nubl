# نظام الإشعارات — Notifications

دليل مبسط لإرسال وعرض الإشعارات في NUBL.

---

## نظرة عامة

| المكون | الوظيفة |
|--------|---------|
| **جدول notifications** | تخزين الإشعارات (Laravel Notifications) |
| **DonationReceiptNotification** | إشعار إيصال التبرع (قاعدة بيانات + بريد) |
| **NotificationController** | API لجلب الإشعارات وتحديد المقروء |
| **notificationPanel** | مكوّن Alpine.js لعرض الإشعارات في الهيدر |

**شرط:** النموذج `User` يجب أن يستخدم الـ trait `Notifiable` (موجود افتراضياً في `App\Models\User`).

---

## إرسال إشعار (سطر واحد)

```php
$user->notify(new DonationReceiptNotification($payment));
```

أو عبر NotificationService (للخدمات):

```php
$this->notificationService->sendDonationReceipt($payment);
```

---

## تنبيه هندسي (إلزامي)

لتوحيد التصميم ورفع الـ SOLID: أي كود يتعامل مع الإشعارات يجب أن يلتزم باستخدام الواجهة.

1. داخل الـ `Services` أو الـ `Controllers` لا تستخدم `\$user->notify(...)` مباشرة.
2. بدلاً من ذلك حقن واستخدام `App\Contracts\NotificationServiceInterface` (مثل `$this->notificationService->sendDonationReceipt(...)`).
3. الهدف: فصل منطق الإشعارات عن منطق الدفع/التسجيل وتقليل الاقتران (SRP/DIP).

---

## إضافة إشعار جديد — 3 خطوات

### 1. إنشاء كلاس الإشعار

```bash
php artisan make:notification RequestApprovedNotification
```

### 2. تعريف الإشعار (يجب أن يحتوي `type` في toArray)

```php
// app/Notifications/RequestApprovedNotification.php
public function toArray(object $notifiable): array
{
    return [
        'type' => 'request_approved',   // ← مطلوب
        'message' => __('Your request was approved'),
        'url' => route('recipient.requests.index'),
        // 'subtitle' => __('Optional') — اختياري، إن وُجد ي override القيمة في config
    ];
}
```

### 3. إضافة في config

```php
// config/notifications.php — داخل مصفوفة 'types'
'request_approved' => [
    'icon' => 'success',
    'icon_svg' => 'check-circle',
    'subtitle' => 'Your request was approved',
],
```

**انتهى.** لا حاجة لتعديل NotificationController.

---

## مرجع سريع — الأيقونات

| العنصر | القيم |
|--------|-------|
| **icon** | `success` \| `warning` \| `info` \| `primary` |
| **icon_svg** | `check-circle` \| `bell` \| `clock` \| `users` |

| icon_svg | اللون | الاستخدام |
|----------|-------|-----------|
| `check-circle` | success (أخضر) | نجاح، إكمال |
| `bell` | info (أزرق) | تنبيه عام |
| `clock` | warning (أصفر) | معلق، انتظار |
| `users` | primary | مستخدمون، مجموعات |

---

## المسارات (Routes)

جميع المسارات تتطلب تسجيل الدخول (`auth` middleware).

| المسار | الطريقة | الوظيفة |
|--------|---------|---------|
| `GET /notifications` | GET | جلب الإشعارات (JSON) |
| `POST /notifications/{id}/read` | POST | تحديد إشعار كمقروء |
| `POST /notifications/read-all` | POST | تحديد الكل كمقروء |

---

## القنوات والبريد

- **database:** تخزين الإشعار في جدول `notifications` (مطلوب لعرضه في الواجهة).
- **mail:** إرسال بريد إلكتروني (اختياري).

إذا أردت إرسال بريد مع الإشعار، عرّف القنوات و`toMail`:

```php
public function via(object $notifiable): array
{
    $channels = ['database'];
    if (!empty($notifiable->email)) {
        $channels[] = 'mail';
    }
    return $channels;
}

public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject(__('Subject'))
        ->line(__('Line 1'))
        ->action(__('Action'), url('/path'));
}
```

---

## API الاستجابة (GET /notifications)

```json
{
  "notifications": [
    {
      "id": "uuid",
      "type": "donation_receipt",
      "read_at": null,
      "created_at": "2026-03-02T12:00:00.000000Z",
      "title": "شكراً لك! تمت عملية التبرع بمبلغ 100.00 ريال بنجاح.",
      "subtitle": "تم إرسال الإيصال إلى بريدك الإلكتروني",
      "url": "http://localhost/donor/donations",
      "icon": "success",
      "icon_svg": "check-circle"
    }
  ],
  "unread_count": 1
}
```

---

## الواجهة الأمامية (Frontend)

- جلب الإشعارات عند فتح القائمة، وتحديث تلقائي كل **30 ثانية** (polling).
- المكوّن: `resources/js/components/notificationPanel.js`.

استخدام الـ API من JavaScript:

```javascript
// جلب الإشعارات
const res = await fetch('/notifications', {
  headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  credentials: 'same-origin',
});
const { notifications, unread_count } = await res.json();

// تحديد كمقروء
await fetch(`/notifications/${id}/read`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
  },
  credentials: 'same-origin',
});
```

---

## التنفيذ المتزامن vs قائمة الانتظار

- **DonationReceiptNotification** يعمل **مباشرة** (بدون Queue) حتى يظهر الإشعار فوراً.
- إذا أردت استخدام `ShouldQueue` لإشعارات أخرى، شغّل:
  ```bash
  php artisan queue:work
  ```

---

## الترجمات

أضف نصوص الإشعارات في `lang/ar.json` (أو الملف المناسب للغة):

```json
"Your notification message": "رسالة الإشعار بالعربية"
```

---

## التوافق

بعد إضافة **NotificationService** و **config/notifications.php**:

| المكون | الحالة |
|--------|--------|
| **DonationReceiptNotification** | لم يتغير — يعمل كما هو (database + mail) |
| **NotificationController** و **formatNotification()** | يقرأ من الـ config بدلاً من `match` ثابت — السلوك نفسه |
| **notificationPanel.js** | لم يتغير — يعرض الإشعارات كما قبل |
| **PaymentFlowTest** | تعمل كما هي — NotificationService يُحقَن تلقائياً من الحاوية |
| **PaymentService** | يعتمد على `NotificationServiceInterface` بدلاً من `DonationReceiptNotification` مباشرة |

التطبيق يعمل بشكل طبيعي؛ الإرسال يتم عبر NotificationService من الخدمات، والعرض والـ API كما كان.

---

## الملفات المرجعية

| الملف | الوظيفة |
|-------|---------|
| `config/notifications.php` | تسجيل أنواع الإشعارات (icon, icon_svg, subtitle) |
| `app/Notifications/*.php` | كلاسات الإشعارات |
| `app/Contracts/NotificationServiceInterface.php` | واجهة خدمة الإشعارات |
| `app/Http/Services/NotificationService.php` | إرسال الإشعارات من الخدمات |
| `app/Http/Controllers/NotificationController.php` | API الإشعارات |
| `resources/js/components/notificationPanel.js` | عرض الإشعارات (polling كل 30 ثانية) |
| `resources/views/components/app-partials/header.blade.php` | مكان عرض قائمة الإشعارات في الهيدر |
| `database/migrations/*_create_notifications_table.php` | جدول الإشعارات |
| `lang/ar.json` (أو ملفات اللغة) | ترجمة نصوص الإشعارات |
