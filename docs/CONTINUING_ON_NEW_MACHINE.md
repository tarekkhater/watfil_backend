# متابعة العمل من لابتوب جديد

## 1. استنساخ المشروع

```bash
git clone https://github.com/tarekkhater/watfil_backend.git
cd watfil_backend
git checkout feature/kero
```

## 2. إعداد البيئة

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## 3. أين الخطة؟

| الملف | الاستخدام |
|-------|-----------|
| [BACKEND_ROADMAP.md](BACKEND_ROADMAP.md) | خارطة الطريق الكاملة (Plan 1–21) |
| [../PROJECT_GUIDE_AR.md](../PROJECT_GUIDE_AR.md) | ما تم تنفيذه + كل الـ endpoints |
| [../Watfil_API_Complete.postman_collection.json](../Watfil_API_Complete.postman_collection.json) | اختبار Postman |

## 4. متابعة في Cursor

1. افتح مجلد `watfil_backend` في Cursor
2. في الشات اكتب مثلاً:
   ```
   @docs/BACKEND_ROADMAP.md نفّذ Plan 2
   ```
3. حدّث `status` في أول ملف الخطة عند إكمال كل plan (`completed` / `in_progress`)

## 5. حسابات الاختبار

- Super Admin: `admin@watafl.com` / `Admin@1234`
- Company: أنشئها عبر Postman → **00 Setup Flow**
