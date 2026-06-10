# Watafl API — توثيق المشروع

> **للمطورين الفرونت:** راجع [API_ENDPOINTS.md](API_ENDPOINTS.md) — توثيق منفصل مخصص للـ endpoints مع أمثلة Axios و TypeScript types.

## جدول المحتويات
1. [نظرة عامة](#نظرة-عامة)
2. [متطلبات التشغيل](#متطلبات-التشغيل)
3. [تثبيت المشروع](#تثبيت-المشروع)
4. [هيكل المشروع](#هيكل-المشروع)
5. [قاعدة البيانات](#قاعدة-البيانات)
6. [المصادقة](#المصادقة)
7. [فهرس كل الـ Endpoints](#فهرس-كل-ال-endpoints)
8. [Super Admin API](#super-admin-api)
9. [Company API](#company-api)
10. [رسائل الخطأ](#رسائل-الخطأ)
11. [ملاحظات تقنية](#ملاحظات-تقنية)

---

## نظرة عامة

**Watafl** هو مشروع Backend API مبني على **Laravel 11** يتيح إدارة منظومة متكاملة من:

| الدور | الوصف |
|---|---|
| **Super Admin** | يدير الشركات والموردين ومنتجات الموردين |
| **Company** | تدير منتجاتها الخاصة وتختار من كتالوج الموردين |

---

## متطلبات التشغيل

| المتطلب | الإصدار |
|---|---|
| PHP | >= 8.2 |
| Laravel | 11.x |
| MySQL | >= 5.7 |
| Composer | >= 2.x |

---

## تثبيت المشروع

```bash
# 1. الدخول على مجلد المشروع
cd watafl

# 2. تثبيت الـ dependencies
composer install

# 3. نسخ ملف الـ environment
cp .env.example .env

# 4. توليد الـ app key
php artisan key:generate

# 5. إعداد قاعدة البيانات في .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=watafl
DB_USERNAME=root
DB_PASSWORD=

# 6. تشغيل الـ migrations والـ seeders
php artisan migrate --seed

# 7. إنشاء رابط الـ storage
php artisan storage:link

# 8. تشغيل السيرفر
php artisan serve
```

بعد الـ seed، السيرفر يكون جاهز على `http://localhost:8000`

### بيانات الدخول الافتراضية

| الحساب | البيانات |
|---|---|
| Super Admin Email | `admin@watafl.com` |
| Super Admin Password | `Admin@1234` |

---

## هيكل المشروع

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── SuperAdmin/
│   │   │   ├── AuthController.php          ← تسجيل دخول/خروج السوبر أدمن
│   │   │   ├── GovernorateController.php   ← عرض المحافظات
│   │   │   ├── CompanyController.php       ← CRUD الشركات
│   │   │   ├── SupplierController.php      ← CRUD الموردين
│   │   │   └── SupplierProductController.php ← CRUD منتجات الموردين
│   │   └── Company/
│   │       ├── AuthController.php          ← تسجيل دخول/خروج الشركة
│   │       ├── ProductController.php       ← CRUD منتجات الشركة الخاصة
│   │       └── CatalogController.php       ← إدارة كتالوج الموردين
│   ├── Middleware/
│   │   ├── SuperAdminMiddleware.php        ← حماية routes السوبر أدمن
│   │   └── CompanyMiddleware.php           ← حماية routes الشركة
│   ├── Requests/
│   │   ├── SuperAdmin/
│   │   │   ├── StoreCompanyRequest.php
│   │   │   ├── UpdateCompanyRequest.php
│   │   │   ├── StoreSupplierRequest.php
│   │   │   ├── UpdateSupplierRequest.php
│   │   │   ├── StoreSupplierProductRequest.php
│   │   │   └── UpdateSupplierProductRequest.php
│   │   └── Company/
│   │       ├── StoreCompanyProductRequest.php
│   │       └── UpdateCompanyProductRequest.php
│   └── Resources/
│       ├── GovernorateResource.php
│       ├── CompanyResource.php
│       ├── SupplierResource.php
│       ├── SupplierProductResource.php
│       └── CompanyProductResource.php
├── Models/
│   ├── SuperAdmin.php
│   ├── Governorate.php
│   ├── Company.php
│   ├── Supplier.php
│   ├── SupplierProduct.php
│   └── CompanyProduct.php
database/
├── migrations/          ← كل جداول قاعدة البيانات
└── seeders/
    ├── GovernorateSeeder.php   ← 27 محافظة مصرية
    └── SuperAdminSeeder.php    ← Super Admin افتراضي
routes/
└── api.php              ← كل الـ API routes
```

---

## قاعدة البيانات

### مخطط الجداول

```
governorates
├── id
├── name_ar          (القاهرة، الإسكندرية، ...)
├── name_en          (Cairo, Alexandria, ...)
└── timestamps

super_admins
├── id
├── name
├── email            (unique — يُستخدم للدخول)
├── password         (hashed)
└── timestamps

companies
├── id
├── name
├── tax_number       (unique — يُستخدم للدخول)
├── password         (hashed)
├── governorate_id   (FK → governorates)
├── logo             (nullable — مسار الصورة)
├── is_active        (boolean، افتراضي true)
└── timestamps

suppliers
├── id
├── name
├── logo             (nullable)
├── description      (nullable)
└── timestamps

supplier_products
├── id
├── name
├── description      (nullable)
├── image            (nullable)
├── price            (decimal 10,2)
├── supplier_id      (FK → suppliers، cascade delete)
├── is_active        (boolean، افتراضي true)
└── timestamps

company_products
├── id
├── name
├── description      (nullable)
├── image            (nullable)
├── price            (decimal 10,2)
├── company_id       (FK → companies، cascade delete)
├── is_active        (boolean، افتراضي true)
└── timestamps

company_catalog       ← pivot table
├── id
├── company_id        (FK → companies، cascade delete)
├── supplier_product_id (FK → supplier_products، cascade delete)
├── unique(company_id, supplier_product_id)
└── timestamps
```

### العلاقات

```
Governorate     ──< Company             (one-to-many)
Supplier        ──< SupplierProduct     (one-to-many)
Company         ──< CompanyProduct      (one-to-many)
Company         >──< SupplierProduct    (many-to-many عبر company_catalog)
```

### المحافظات المصرية (27 محافظة)

القاهرة، الإسكندرية، الجيزة، القليوبية، الشرقية، الدقهلية، البحيرة، كفر الشيخ، الغربية، المنوفية، الفيوم، بني سويف، المنيا، أسيوط، سوهاج، قنا، الأقصر، أسوان، البحر الأحمر، الوادي الجديد، مطروح، شمال سيناء، جنوب سيناء، بورسعيد، الإسماعيلية، السويس، دمياط.

---

## المصادقة

المشروع يستخدم **Laravel Sanctum** — Token-based Authentication.

### آلية العمل

```
1. المستخدم يبعت بيانات الدخول (POST /login)
2. السيرفر يرجع Bearer Token
3. المستخدم يبعت الـ Token في كل request في الـ Header:
   Authorization: Bearer {token}
4. عند الخروج (POST /logout) الـ Token بيتحذف
```

### Guards المستخدمة

| Guard | Model | يُستخدم في |
|---|---|---|
| `sanctum` | `SuperAdmin` | `/api/super-admin/*` |
| `sanctum` | `Company` | `/api/company/*` |

### Middleware

| Middleware | الوظيفة |
|---|---|
| `auth:sanctum` | يتحقق أن الـ Token صحيح وموجود |
| `super_admin` | يتحقق أن المستخدم هو `SuperAdmin` model |
| `company` | يتحقق أن المستخدم هو `Company` model وأنه مفعّل |

### Headers المطلوبة

| Header | القيمة | متى |
|---|---|---|
| `Accept` | `application/json` | كل الـ requests |
| `Content-Type` | `application/json` | طلبات JSON (login، catalog/add، catalog/remove) |
| `Content-Type` | `multipart/form-data` | طلبات رفع صور (شركات، موردين، منتجات) |
| `Authorization` | `Bearer {token}` | كل الـ endpoints المحمية (🔒) |

> **Base URL للـ API:** `http://localhost:8000/api`

---

## فهرس كل الـ Endpoints

| # | Method | المسار الكامل | المصادقة | الوصف |
|---|---|---|---|---|
| 1 | `POST` | `/api/super-admin/login` | — | تسجيل دخول السوبر أدمن |
| 2 | `POST` | `/api/super-admin/logout` | 🔒 Super Admin | تسجيل خروج وحذف الـ Token |
| 3 | `GET` | `/api/super-admin/me` | 🔒 Super Admin | بيانات السوبر أدمن الحالي |
| 4 | `GET` | `/api/super-admin/governorates` | 🔒 Super Admin | قائمة المحافظات المصرية (27) |
| 5 | `GET` | `/api/super-admin/companies` | 🔒 Super Admin | قائمة الشركات (paginated) |
| 6 | `POST` | `/api/super-admin/companies` | 🔒 Super Admin | إنشاء شركة جديدة |
| 7 | `GET` | `/api/super-admin/companies/{id}` | 🔒 Super Admin | تفاصيل شركة واحدة |
| 8 | `POST` | `/api/super-admin/companies/{id}` | 🔒 Super Admin | تعديل بيانات شركة |
| 9 | `DELETE` | `/api/super-admin/companies/{id}` | 🔒 Super Admin | حذف شركة |
| 10 | `PATCH` | `/api/super-admin/companies/{id}/toggle-status` | 🔒 Super Admin | تفعيل/تعطيل شركة |
| 11 | `GET` | `/api/super-admin/suppliers` | 🔒 Super Admin | قائمة الموردين (paginated) |
| 12 | `POST` | `/api/super-admin/suppliers` | 🔒 Super Admin | إنشاء مورد جديد |
| 13 | `GET` | `/api/super-admin/suppliers/{id}` | 🔒 Super Admin | تفاصيل مورد واحد |
| 14 | `POST` | `/api/super-admin/suppliers/{id}` | 🔒 Super Admin | تعديل بيانات مورد |
| 15 | `DELETE` | `/api/super-admin/suppliers/{id}` | 🔒 Super Admin | حذف مورد (يحذف منتجاته) |
| 16 | `GET` | `/api/super-admin/supplier-products` | 🔒 Super Admin | قائمة منتجات الموردين |
| 17 | `POST` | `/api/super-admin/supplier-products` | 🔒 Super Admin | إضافة منتج لمورد |
| 18 | `GET` | `/api/super-admin/supplier-products/{id}` | 🔒 Super Admin | تفاصيل منتج مورد |
| 19 | `POST` | `/api/super-admin/supplier-products/{id}` | 🔒 Super Admin | تعديل منتج مورد |
| 20 | `DELETE` | `/api/super-admin/supplier-products/{id}` | 🔒 Super Admin | حذف منتج مورد |
| 21 | `PATCH` | `/api/super-admin/supplier-products/{id}/toggle-status` | 🔒 Super Admin | تفعيل/إيقاف منتج مورد |
| 22 | `POST` | `/api/company/login` | — | تسجيل دخول الشركة |
| 23 | `POST` | `/api/company/logout` | 🔒 Company | تسجيل خروج الشركة |
| 24 | `GET` | `/api/company/me` | 🔒 Company | بيانات الشركة الحالية |
| 25 | `GET` | `/api/company/products` | 🔒 Company | قائمة منتجات الشركة الخاصة |
| 26 | `POST` | `/api/company/products` | 🔒 Company | إضافة منتج خاص بالشركة |
| 27 | `GET` | `/api/company/products/{id}` | 🔒 Company | تفاصيل منتج خاص |
| 28 | `POST` | `/api/company/products/{id}` | 🔒 Company | تعديل منتج خاص |
| 29 | `DELETE` | `/api/company/products/{id}` | 🔒 Company | حذف منتج خاص |
| 30 | `GET` | `/api/company/catalog/available` | 🔒 Company | منتجات الموردين المتاحة للاختيار |
| 31 | `GET` | `/api/company/catalog/mine` | 🔒 Company | منتجات الموردين في كتالوج الشركة |
| 32 | `POST` | `/api/company/catalog/add` | 🔒 Company | إضافة منتجات متعددة للكتالوج |
| 33 | `POST` | `/api/company/catalog/remove` | 🔒 Company | إزالة منتجات متعددة من الكتالوج |
| 34 | `POST` | `/api/company/catalog/{id}` | 🔒 Company | إضافة منتج واحد للكتالوج |
| 35 | `DELETE` | `/api/company/catalog/{id}` | 🔒 Company | إزالة منتج واحد من الكتالوج |

**المجموع: 35 endpoint API**

---

## Super Admin API

**Base URL:** `http://localhost:8000/api/super-admin`

### المصادقة

#### `POST /login`
تسجيل دخول السوبر أدمن.

**Request Body (JSON):**
```json
{
  "email": "admin@watafl.com",
  "password": "Admin@1234"
}
```

**Response (200):**
```json
{
  "message": "تم تسجيل الدخول بنجاح",
  "token": "1|abc123...",
  "admin": {
    "id": 1,
    "name": "Super Admin",
    "email": "admin@watafl.com"
  }
}
```

---

#### `POST /logout` 🔒
تسجيل الخروج وحذف الـ Token.

**Response (200):**
```json
{ "message": "تم تسجيل الخروج بنجاح" }
```

---

#### `GET /me` 🔒
جلب بيانات السوبر أدمن الحالي.

**Response (200):**
```json
{
  "id": 1,
  "name": "Super Admin",
  "email": "admin@watafl.com",
  "created_at": "2026-05-21T10:00:00.000000Z",
  "updated_at": "2026-05-21T10:00:00.000000Z"
}
```

---

### المحافظات

#### `GET /governorates` 🔒
جلب كل المحافظات المصرية الـ 27.

**Response (200):**
```json
{
  "data": [
    { "id": 1, "name_ar": "القاهرة", "name_en": "Cairo" },
    { "id": 2, "name_ar": "الإسكندرية", "name_en": "Alexandria" }
  ]
}
```

---

### الشركات

#### `GET /companies` 🔒
جلب كل الشركات مع Pagination.

**Query Params:**
| Param | النوع | الوصف |
|---|---|---|
| `page` | integer | رقم الصفحة (افتراضي 1) |

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "شركة الأمل",
      "tax_number": "TAX-001",
      "is_active": true,
      "logo": "http://localhost:8000/storage/logos/companies/xxx.jpg",
      "governorate": { "id": 1, "name_ar": "القاهرة", "name_en": "Cairo" },
      "created_at": "2026-05-21 10:00:00"
    }
  ],
  "meta": {
    "total": 50,
    "current_page": 1,
    "last_page": 4,
    "per_page": 15
  }
}
```

---

#### `POST /companies` 🔒
إنشاء شركة جديدة.

**Request Body (form-data):**
| Field | النوع | المطلوب | الوصف |
|---|---|---|---|
| `name` | string | ✅ | اسم الشركة |
| `tax_number` | string | ✅ | الرقم الضريبي (فريد، يُستخدم للدخول) |
| `password` | string | ✅ | كلمة المرور (8 أحرف minimum) |
| `governorate_id` | integer | ✅ | ID المحافظة |
| `logo` | file | ❌ | صورة jpg/png/webp بحد أقصى 2MB |
| `is_active` | boolean | ❌ | افتراضي `1` |

**Response (201):**
```json
{
  "message": "تم إنشاء الشركة بنجاح",
  "data": { ... }
}
```

---

#### `GET /companies/{id}` 🔒
جلب تفاصيل شركة واحدة.

**URL Param:** `{id}` — معرّف الشركة

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "شركة الأمل",
    "tax_number": "TAX-001",
    "is_active": true,
    "logo": "http://localhost:8000/storage/logos/companies/xxx.jpg",
    "governorate": { "id": 1, "name_ar": "القاهرة", "name_en": "Cairo" },
    "created_at": "2026-05-21 10:00:00"
  }
}
```

---

#### `POST /companies/{id}` 🔒
تعديل بيانات شركة (كل الحقول اختيارية، يُرسل المطلوب تغييره فقط).

**Request Body (form-data):** نفس حقول الإنشاء لكن كلها اختيارية (`sometimes`).

**Response (200):**
```json
{
  "message": "تم تحديث الشركة بنجاح",
  "data": { ... }
}
```

---

#### `DELETE /companies/{id}` 🔒
حذف شركة نهائيًا (الصورة تُحذف من الـ storage تلقائيًا).

**Response (200):**
```json
{ "message": "تم حذف الشركة بنجاح" }
```

---

#### `PATCH /companies/{id}/toggle-status` 🔒
تفعيل أو تعطيل شركة.

**Response (200):**
```json
{
  "message": "تم تعطيل الشركة",
  "is_active": false
}
```

---

### الموردين

#### `GET /suppliers` 🔒
جلب كل الموردين مع عدد منتجاتهم (`products_count`).

**Query Params:** `page` (اختياري)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "مورد الأغذية",
      "description": "مورد متخصص في المنتجات الغذائية",
      "logo": "http://localhost:8000/storage/logos/suppliers/xxx.jpg",
      "products_count": 12,
      "created_at": "2026-05-21 10:00:00"
    }
  ],
  "meta": { "total": 5, "current_page": 1, "last_page": 1, "per_page": 15 }
}
```

---

#### `POST /suppliers` 🔒
إنشاء مورد جديد.

**Request Body (form-data):**
| Field | النوع | المطلوب | الوصف |
|---|---|---|---|
| `name` | string | ✅ | اسم المورد |
| `description` | string | ❌ | وصف المورد |
| `logo` | file | ❌ | صورة jpg/png/webp بحد أقصى 2MB |

**Response (201):**
```json
{
  "message": "تم إنشاء المورد بنجاح",
  "data": {
    "id": 1,
    "name": "مورد الأغذية",
    "description": null,
    "logo": null,
    "created_at": "2026-05-21 10:00:00"
  }
}
```

---

#### `GET /suppliers/{id}` 🔒
جلب تفاصيل مورد مع عدد منتجاته.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "مورد الأغذية",
    "description": "وصف المورد",
    "logo": "http://localhost:8000/storage/logos/suppliers/xxx.jpg",
    "products_count": 12,
    "created_at": "2026-05-21 10:00:00"
  }
}
```

---

#### `POST /suppliers/{id}` 🔒
تعديل بيانات مورد (كل الحقول اختيارية).

**Response (200):**
```json
{
  "message": "تم تحديث المورد بنجاح",
  "data": { ... }
}
```

---

#### `DELETE /suppliers/{id}` 🔒
حذف مورد. **تنبيه:** حذف المورد يحذف كل منتجاته تلقائيًا (cascade).

**Response (200):**
```json
{ "message": "تم حذف المورد بنجاح" }
```

---

### منتجات الموردين

#### `GET /supplier-products` 🔒
جلب منتجات الموردين مع فلاتر.

**Query Params:**
| Param | الوصف |
|---|---|
| `page` | رقم الصفحة |
| `supplier_id` | فلترة حسب مورد معين |
| `is_active` | فلترة حسب الحالة: `1` أو `0` |

#### `POST /supplier-products` 🔒
إضافة منتج جديد لمورد.

**Request Body (form-data):**
| Field | النوع | المطلوب | الوصف |
|---|---|---|---|
| `name` | string | ✅ | اسم المنتج |
| `price` | decimal | ✅ | السعر (رقم موجب) |
| `supplier_id` | integer | ✅ | ID المورد |
| `description` | string | ❌ | وصف المنتج |
| `image` | file | ❌ | صورة jpg/png/webp بحد أقصى 2MB |
| `is_active` | boolean | ❌ | افتراضي `1` |

**ملاحظة:** بعد الإنشاء يصبح المنتج متاحًا لكل الشركات تختاره في متجرها.

**Response (201):**
```json
{
  "message": "تم إنشاء المنتج بنجاح",
  "data": {
    "id": 1,
    "name": "زيت زيتون",
    "description": "زيت زيتون بكر ممتاز",
    "image": "http://localhost:8000/storage/products/supplier/xxx.jpg",
    "price": "150.00",
    "is_active": true,
    "supplier": {
      "id": 1,
      "name": "مورد الأغذية",
      "description": null,
      "logo": null,
      "created_at": "2026-05-21 10:00:00"
    },
    "created_at": "2026-05-21 10:00:00"
  }
}
```

---

#### `GET /supplier-products/{id}` 🔒
جلب تفاصيل منتج مع بيانات المورد.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "زيت زيتون",
    "description": "زيت زيتون بكر ممتاز",
    "image": "http://localhost:8000/storage/products/supplier/xxx.jpg",
    "price": "150.00",
    "is_active": true,
    "supplier": { "id": 1, "name": "مورد الأغذية", ... },
    "created_at": "2026-05-21 10:00:00"
  }
}
```

---

#### `POST /supplier-products/{id}` 🔒
تعديل منتج (كل الحقول اختيارية).

**Response (200):**
```json
{
  "message": "تم تحديث المنتج بنجاح",
  "data": { ... }
}
```

---

#### `DELETE /supplier-products/{id}` 🔒
حذف منتج (الصورة تُحذف تلقائيًا).

**Response (200):**
```json
{ "message": "تم حذف المنتج بنجاح" }
```

---

#### `PATCH /supplier-products/{id}/toggle-status` 🔒
تفعيل أو إيقاف منتج. المنتجات الموقوفة لا تظهر للشركات.

**Response (200):**
```json
{
  "message": "تم تعطيل المنتج",
  "is_active": false
}
```

---

## Company API

**Base URL:** `http://localhost:8000/api/company`

### المصادقة

#### `POST /login`
تسجيل دخول الشركة بالرقم الضريبي.

**Request Body (JSON):**
```json
{
  "tax_number": "TAX-2024-001",
  "password": "Company@1234"
}
```

**Response (200):**
```json
{
  "message": "تم تسجيل الدخول بنجاح",
  "token": "2|xyz789...",
  "company": {
    "id": 1,
    "name": "شركة الأمل",
    "tax_number": "TAX-2024-001",
    "is_active": true,
    "logo": null,
    "governorate": { "id": 1, "name_ar": "القاهرة", "name_en": "Cairo" },
    "created_at": "2026-05-21 10:00:00"
  }
}
```

**حالات الخطأ:**
- `422` — بيانات غلط
- `403` — الحساب موقوف (`is_active = false`)

---

#### `POST /logout` 🔒
تسجيل الخروج.

**Response (200):**
```json
{ "message": "تم تسجيل الخروج بنجاح" }
```

---

#### `GET /me` 🔒
جلب بيانات الشركة الحالية مع المحافظة.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "شركة الأمل",
    "tax_number": "TAX-2024-001",
    "is_active": true,
    "logo": null,
    "governorate": { "id": 1, "name_ar": "القاهرة", "name_en": "Cairo" },
    "created_at": "2026-05-21 10:00:00"
  }
}
```

---

### منتجات الشركة الخاصة

#### `GET /products` 🔒
جلب منتجات الشركة الخاصة (منتجاتها هي فقط).

**Query Params:** `page` (اختياري)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "منتج الشركة",
      "description": "وصف المنتج",
      "image": "http://localhost:8000/storage/products/company/xxx.jpg",
      "price": "99.99",
      "is_active": true,
      "created_at": "2026-05-21 10:00:00"
    }
  ],
  "meta": { "total": 10, "current_page": 1, "last_page": 1, "per_page": 15 }
}
```

---

#### `POST /products` 🔒
إضافة منتج خاص بالشركة.

**Request Body (form-data):**
| Field | النوع | المطلوب | الوصف |
|---|---|---|---|
| `name` | string | ✅ | اسم المنتج |
| `price` | decimal | ✅ | السعر |
| `description` | string | ❌ | الوصف |
| `image` | file | ❌ | صورة jpg/png/webp بحد أقصى 2MB |
| `is_active` | boolean | ❌ | افتراضي `1` |

**Response (201):**
```json
{
  "message": "تم إضافة المنتج بنجاح",
  "data": {
    "id": 1,
    "name": "منتج الشركة",
    "description": null,
    "image": null,
    "price": "99.99",
    "is_active": true,
    "created_at": "2026-05-21 10:00:00"
  }
}
```

---

#### `GET /products/{id}` 🔒
جلب منتج خاص. **أمان:** الشركة تقدر تشوف منتجاتها بس — محاولة الوصول لمنتج شركة أخرى ترجع `403`.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "منتج الشركة",
    "description": "وصف",
    "image": null,
    "price": "99.99",
    "is_active": true,
    "created_at": "2026-05-21 10:00:00"
  }
}
```

---

#### `POST /products/{id}` 🔒
تعديل منتج خاص (كل الحقول اختيارية).

**Response (200):**
```json
{
  "message": "تم تحديث المنتج بنجاح",
  "data": { ... }
}
```

---

#### `DELETE /products/{id}` 🔒
حذف منتج خاص.

**Response (200):**
```json
{ "message": "تم حذف المنتج بنجاح" }
```

---

### كتالوج الموردين

الشركة تقدر تختار من منتجات الموردين وتضيفها لمتجرها بجانب منتجاتها الخاصة.

#### `GET /catalog/available` 🔒
جلب كل منتجات الموردين المتاحة للاختيار.

**Query Params:**
| Param | الوصف |
|---|---|
| `page` | رقم الصفحة |
| `supplier_id` | فلترة حسب مورد معين |

#### `GET /catalog/mine` 🔒
جلب المنتجات اللي الشركة اختارتها من كتالوج الموردين.

**Query Params:** `page` (اختياري)

**Response (200):** نفس شكل `supplier-products` مع بيانات المورد.

---

#### `POST /catalog/add` 🔒
إضافة منتجات متعددة للكتالوج دفعة واحدة.

**Request Body (JSON):**
```json
{
  "product_ids": [1, 2, 3]
}
```

**ملاحظة:** لو المنتج موجود بالفعل في الكتالوج لا يتكرر. المنتجات غير المفعّلة (`is_active = false`) يتم تجاهلها تلقائيًا.

**Response (200):**
```json
{ "message": "تم إضافة المنتجات إلى متجرك بنجاح" }
```

---

#### `POST /catalog/remove` 🔒
إزالة منتجات متعددة من الكتالوج دفعة واحدة.

**Request Body (JSON):**
```json
{
  "product_ids": [1, 2]
}
```

**Response (200):**
```json
{ "message": "تم إزالة المنتجات من متجرك بنجاح" }
```

---

#### `POST /catalog/{id}` 🔒
إضافة منتج واحد للكتالوج.

**URL Param:** `{id}` — معرّف منتج المورد (موجود في الـ route لكن الـ Controller يعتمد على الـ body)

**Request Body (JSON):**
```json
{
  "supplier_product_id": 5
}
```

| Field | النوع | المطلوب | الوصف |
|---|---|---|---|
| `supplier_product_id` | integer | ✅ | ID منتج المورد (يجب أن يكون موجودًا ومفعّلًا) |

**Response (200):**
```json
{ "message": "تم إضافة المنتج إلى متجرك بنجاح" }
```

**أخطاء محتملة:**
- `422` — المنتج غير متاح (`is_active = false`): `{ "message": "هذا المنتج غير متاح حاليًا" }`

---

#### `DELETE /catalog/{id}` 🔒
إزالة منتج واحد من الكتالوج.

**URL Param:** `{id}` — معرّف منتج المورد

**Response (200):**
```json
{ "message": "تم إزالة المنتج من متجرك بنجاح" }
```

---

## رسائل الخطأ

### أكواد الاستجابة

| Code | المعنى |
|---|---|
| `200` | ناجح |
| `201` | تم الإنشاء |
| `401` | غير مصادق (Token مش موجود أو منتهي) |
| `403` | غير مصرح (نوع حساب غلط أو حساب موقوف) |
| `404` | العنصر مش موجود |
| `422` | بيانات غلط (Validation Error) |
| `500` | خطأ في السيرفر |

### شكل رسالة الـ Validation Error

```json
{
  "message": "The name field is required.",
  "errors": {
    "name": ["اسم الشركة مطلوب"],
    "tax_number": ["الرقم الضريبي مستخدم بالفعل"]
  }
}
```

---

## ملاحظات تقنية

### رفع الصور

- الصور تتخزن في `storage/app/public/`
- المسارات:
  - شعارات الشركات: `logos/companies/`
  - شعارات الموردين: `logos/suppliers/`
  - صور منتجات الموردين: `products/supplier/`
  - صور منتجات الشركات: `products/company/`
- لازم تشغّل `php artisan storage:link` عشان تشتغل الـ URLs
- الـ URL بيبان في الـ response هكذا: `http://localhost:8000/storage/logos/...`

### Pagination

كل الـ listing endpoints بترجع:
```json
{
  "data": [...],
  "meta": {
    "total": 100,
    "current_page": 1,
    "last_page": 7,
    "per_page": 15
  }
}
```

### استخدام `POST` بدل `PUT/PATCH` للتعديل

الـ update endpoints بتستخدم `POST` بدل `PUT` عشان HTML forms مش بتدعم `PUT` مع `multipart/form-data` (رفع الصور). في الـ API clients زي Postman ممكن تستخدم `POST` عادي.

### أمان الـ Middleware

```
Request ──→ auth:sanctum ──→ super_admin/company ──→ Controller
```

- `auth:sanctum` بيتحقق من صحة الـ Token
- `super_admin` بيتحقق أن الـ authenticated model هو `SuperAdmin` وليس `Company`
- `company` بيتحقق أن الـ model هو `Company` وأنه `is_active = true`

### Postman Collection

الملف `Watafl.postman_collection.json` موجود في جذر المشروع ويحتوي على:
- كل الـ 35 endpoint جاهزة
- حفظ الـ Token تلقائيًا بعد الـ Login
- Collection Variables: `base_url`، `super_admin_token`، `company_token`

لاستيراده: Postman → Import → اختار الملف.
