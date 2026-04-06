# Path: /README.md

## Enterprise ERP - Step 2

### ما تم في هذه الخطوة
- Module-based Routing
- Users Module
- Settings Module
- Auth Middleware + Guest Middleware
- RBAC Foundation
- إصلاحات لتجنب HTTP 500

### التشغيل
1. ارفع الملفات إلى `/20/accounting/`
2. استورد `database/migrations/database.sql`
3. تأكد من تفعيل `pdo_mysql` و `mod_rewrite`
4. افتح `https://www.shneler.com/20/accounting/`

### تسجيل الدخول
- admin@erp.local
- password

### عند ظهور 500
راجع الملف: `storage/logs/app.log`
