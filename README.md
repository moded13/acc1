# Enterprise ERP - Accounting System (acc1)

نظام محاسبة متكامل بلغة PHP بدون فريم‌ورك خارجي، يشمل:
- دفتر الأستاذ العام (قيود يومية، ميزان المراجعة، قائمة الدخل، الميزانية، كشف الحساب)
- **الوحدة التجارية**: عملاء، موردون، أصناف، فواتير مبيعات، فواتير مشتريات، سندات قبض ودفع، ضريبة القيمة المضافة (16%)

---

## متطلبات التشغيل

- PHP 8.1+
- MySQL / MariaDB
- Web server (Apache / Nginx) مع إعادة توجيه الطلبات إلى `public/index.php`

---

## خطوات الإعداد

### 1. إعداد قاعدة البيانات الأساسية

نفّذ ملف `acc1.sql` لإنشاء الجداول الأساسية وبيانات الحسابات:

```bash
mysql -u YOUR_USER -p YOUR_DB < acc1.sql
```

أو استورده عبر **phpMyAdmin → Import**.

### 2. تشغيل Migration الوحدة التجارية

نفّذ ملف الـ Migration لإنشاء جداول الوحدة التجارية:

```bash
mysql -u YOUR_USER -p YOUR_DB < database/migrations/2026_04_07_commercial_module.sql
```

أو استورده عبر **phpMyAdmin → Import**.

يقوم هذا الملف بـ:
- إضافة عمود `source_type` و `source_id` إلى جدول `journal_entries`
- إنشاء جداول: `customers`, `suppliers`, `items`, `inventory_movements`
- إنشاء جداول: `sales_invoices`, `sales_invoice_lines`, `purchase_invoices`, `purchase_invoice_lines`
- إنشاء جداول: `receipts`, `payments`
- إنشاء جدول `settings` لتخزين معرّفات حسابات التحكم
- إضافة حسابات التحكم في `chart_of_accounts` (ذمم عملاء، ذمم موردين، مبيعات، مخزون، ضريبة...)
- ملء جدول `settings` تلقائياً بمعرّفات الحسابات

### 3. ضبط إعدادات قاعدة البيانات

عدّل ملف `app/Config/Database.php` وأدخل بيانات الاتصال:

```php
$host = 'localhost';
$db   = 'YOUR_DB_NAME';
$user = 'YOUR_DB_USER';
$pass = 'YOUR_DB_PASSWORD';
```

### 4. التحقق من إعدادات حسابات التحكم

بعد تشغيل الـ Migration، تحقق أن قيم جدول `settings` ممتلئة:

```sql
SELECT * FROM settings;
```

يجب أن تحتوي الحقول مثل `account_ar`, `account_ap`, `account_sales`... على معرّفات الحسابات الصحيحة.  
يمكنك تعديلها يدوياً إذا لزم:

```sql
UPDATE settings SET setting_val = <ACCOUNT_ID> WHERE setting_key = 'account_ar';
```

---

## الاستخدام

### الوحدة التجارية - كيفية إنشاء وترحيل فاتورة مبيعات

1. **أضف عميلاً** من صفحة `/customers`
2. **أضف صنفاً** من صفحة `/items` (مع سعر البيع ونسبة الضريبة 0.16)
3. **أنشئ فاتورة مبيعات** من `/sales-invoices/new`
   - اختر العميل والتاريخ
   - أضف أسطر الفاتورة (الصنف/الكمية/السعر)
   - اضغط "حفظ كمسودة"
4. **ارحّل الفاتورة** من صفحة تفاصيل الفاتورة `/sales-invoices/show?id=N`
   - اضغط "ترحيل الفاتورة"
   - يتم تلقائياً: إنشاء قيد محاسبي (مدين ذمم عملاء / دائن مبيعات + ضريبة)، وإنشاء حركة مخزون (OUT)

### فاتورة المشتريات

1. أضف مورداً من `/suppliers`
2. أنشئ فاتورة مشتريات من `/purchase-invoices/new`
3. ارحّل من صفحة التفاصيل — يُنشئ قيداً (مدين مشتريات + ضريبة / دائن ذمم موردين) وحركة مخزون (IN)

### سندات القبض والدفع

- **سند قبض** (من `/receipts`): DR نقد/بنك — CR ذمم عملاء
- **سند دفع** (من `/payments`): DR ذمم موردين — CR نقد/بنك

---

## هيكل المشروع

```
acc1/
├── app/
│   ├── Config/Database.php          # إعدادات قاعدة البيانات
│   ├── Controllers/                 # Controllers (Web + API)
│   ├── Models/                      # Models
│   ├── Services/PostingService.php  # محرك الترحيل للوحدة التجارية
│   ├── Core/                        # Router, Controller, Model, View
│   └── Views/                       # layout.php, footer.php
├── database/
│   └── migrations/
│       └── 2026_04_07_commercial_module.sql
├── public/
│   └── index.php                    # نقطة الدخول وتعريف الـ Routes
├── acc1.sql                         # Schema أساسي
└── README.md
```

---

## API Reference

جميع الـ APIs ترجع `{status, message, data}` بصيغة JSON.

| الطريقة | المسار | الوصف |
|---------|--------|-------|
| GET | `/api/customers` | قائمة العملاء |
| POST | `/api/customers` | إنشاء عميل |
| GET | `/api/suppliers` | قائمة الموردين |
| POST | `/api/suppliers` | إنشاء مورد |
| GET | `/api/items` | قائمة الأصناف |
| POST | `/api/items` | إنشاء صنف |
| GET | `/api/sales-invoices` | قائمة فواتير المبيعات |
| POST | `/api/sales-invoices` | إنشاء فاتورة مبيعات |
| POST | `/api/sales-invoices/post?id=N` | ترحيل فاتورة مبيعات |
| GET | `/api/purchase-invoices` | قائمة فواتير المشتريات |
| POST | `/api/purchase-invoices` | إنشاء فاتورة مشتريات |
| POST | `/api/purchase-invoices/post?id=N` | ترحيل فاتورة مشتريات |
| GET | `/api/receipts` | قائمة سندات القبض |
| POST | `/api/receipts` | إنشاء سند قبض |
| POST | `/api/receipts/post?id=N` | ترحيل سند قبض |
| GET | `/api/payments` | قائمة سندات الدفع |
| POST | `/api/payments` | إنشاء سند دفع |
| POST | `/api/payments/post?id=N` | ترحيل سند دفع |
