# Watafl — API Endpoints Reference (Frontend)

توثيق منفصل مخصص لمطوري الفرونت إند. يشرح كل endpoint بالتفصيل: الطلب، الاستجابة، الأخطاء، وملاحظات التكامل.

---

## المحتويات

- [إعداد سريع](#إعداد-سريع)
- [المصادقة](#المصادقة)
- [أنواع البيانات المشتركة](#أنواع-البيانات-المشتركة)
- [Super Admin](#super-admin)
  - [Auth](#super-admin--auth)
  - [Governorates](#super-admin--governorates)
  - [Companies](#super-admin--companies)
  - [Suppliers](#super-admin--suppliers)
  - [Supplier Products](#super-admin--supplier-products)
- [Company](#company)
  - [Auth](#company--auth)
  - [Products](#company--products)
  - [Catalog](#company--catalog)
- [Public (بدون تسجيل)](#public)
  - [Governorates](#public--governorates)
  - [Companies](#public--companies)
  - [Store Products](#public--store-products)
- [Customer (المستخدم النهائي)](#customer)
  - [Auth](#customer--auth)
  - [Orders](#customer--orders)
  - [Maintenance Requests](#customer--maintenance-requests)
  - [Company Likes & Ratings](#customer--company-likes--ratings)
- [معالجة الأخطاء](#معالجة-الأخطاء)
- [ملاحظات مهمة للفرونت](#ملاحظات-مهمة-للفرونت)

---

## إعداد سريع

```
Base URL:  http://localhost:8000/api
```

### Headers

| Header | القيمة | متى |
|--------|--------|-----|
| `Accept` | `application/json` | **دائمًا** في كل request |
| `Content-Type` | `application/json` | login، catalog/add، catalog/remove |
| `Content-Type` | `multipart/form-data` | أي request فيه رفع صورة |
| `Authorization` | `Bearer {token}` | كل endpoint محمي |

### مثال Axios interceptor

```js
// بعد login احفظ الـ token
localStorage.setItem('token', response.data.token);

// أضفه تلقائيًا لكل request
axios.defaults.baseURL = 'http://localhost:8000/api';
axios.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  config.headers.Accept = 'application/json';
  return config;
});
```

---

## المصادقة

النظام فيه **ثلاثة أدوار** — كل دور له token خاص:

| الدور | Login endpoint | Token ability |
|-------|----------------|---------------|
| Super Admin | `POST /super-admin/login` | `role:super-admin` |
| Company | `POST /company/login` | `role:company` |
| Customer | `POST /customer/login` أو `POST /customer/register/verify` | `role:customer` |

**Public endpoints** (`/public/*`) لا تحتاج token.

**Flow للفرونت (Customer):**

```
1. تصفح بدون تسجيل → /public/*
2. عند شراء/صيانة → POST /customer/auth/check-phone
3. إذا exists=true → POST /customer/login
4. إذا exists=false → POST /customer/register/request-otp ثم /customer/register/verify
5. احفظ data.token → أكمل الطلب
6. عند 401 → وجّه لشاشة الدخول
```

---

## أنواع البيانات المشتركة

### PaginationMeta

كل endpoint فيه قائمة (list) بيرجع نفس شكل الـ `meta`:

```ts
interface PaginationMeta {
  total: number;        // إجمالي العناصر
  current_page: number; // الصفحة الحالية
  last_page: number;    // آخر صفحة
  per_page: number;     // عناصر في الصفحة (ثابت = 15)
}
```

**Query param:** `?page=2`

---

### Governorate

```ts
interface Governorate {
  id: number;
  name_ar: string;
  name_en: string;
}
```

---

### Company

```ts
interface Company {
  id: number;
  name: string;
  tax_number: string;
  is_active: boolean;
  logo: string | null;       // URL كامل جاهز للعرض في <img>
  governorate: Governorate;  // موجود لما يكون loaded
  created_at: string;          // "2026-05-21 10:00:00"
}
```

---

### Supplier

```ts
interface Supplier {
  id: number;
  name: string;
  description: string | null;
  logo: string | null;         // URL كامل
  products_count?: number;     // موجود في القوائم فقط
  created_at: string;
}
```

---

### SupplierProduct

```ts
interface SupplierProduct {
  id: number;
  name: string;
  description: string | null;
  image: string | null;        // URL كامل
  cash_price: string;          // سعر الكاش — "150.00"
  is_active: boolean;
  installment_plans: CompanyProductInstallmentPlan[];
  supplier: Supplier;          // موجود لما يكون loaded
  created_at: string;
}
```

---

### CompanyProductInstallmentPlan

```ts
interface CompanyProductInstallmentPlan {
  months: number;              // 3 | 6 | 9 | 12 | 15 | 18
  down_payment: string;        // المقدم — "1000.00"
  installment_amount: string;  // قيمة القسط الشهري — "500.00"
}
```

### CompanyProduct

```ts
interface CompanyProduct {
  id: number;
  name: string;
  description: string | null;
  image: string | null;
  cash_price: string;          // سعر الكاش — "99.99"
  is_active: boolean;
  installment_plans: CompanyProductInstallmentPlan[];
  created_at: string;
}
```

---

### Validation Error

```ts
interface ValidationError {
  message: string;
  errors: Record<string, string[]>;  // { "email": ["البيانات المدخلة غير صحيحة."] }
}
```

---

# Super Admin

**Prefix:** `/api/super-admin`

---

## Super Admin — Auth

### 1. تسجيل الدخول

```
POST /super-admin/login
```

| | |
|---|---|
| **Auth** | لا يحتاج token |
| **Content-Type** | `application/json` |

**Body:**

```json
{
  "email": "admin@watafl.com",
  "password": "Admin@1234"
}
```

| Field | Type | Required |
|-------|------|----------|
| `email` | string | ✅ |
| `password` | string | ✅ |

**Response `200`:**

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

**Errors:**

| Status | السبب |
|--------|-------|
| `422` | email أو password غلط |

**للفرونت:** احفظ `token` و `admin` — استخدم `token` في كل request بعد كده.

---

### 2. تسجيل الخروج

```
POST /super-admin/logout
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Super Admin) |
| **Body** | لا يوجد |

**Response `200`:**

```json
{ "message": "تم تسجيل الخروج بنجاح" }
```

**للفرونت:** احذف الـ token من localStorage بعد النجاح.

---

### 3. بياناتي

```
GET /super-admin/me
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Super Admin) |

**Response `200`:**

```json
{
  "id": 1,
  "name": "Super Admin",
  "email": "admin@watafl.com",
  "created_at": "2026-05-21T10:00:00.000000Z",
  "updated_at": "2026-05-21T10:00:00.000000Z"
}
```

**للفرونت:** استخدمه عند فتح التطبيق للتحقق أن الـ token لسه شغال وتحميل بيانات الأدمن.

---

## Super Admin — Governorates

### 4. قائمة المحافظات

```
GET /super-admin/governorates
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Super Admin) |
| **Pagination** | لا — يرجع الـ 27 محافظة كلهم |

**Response `200`:**

```json
{
  "data": [
    { "id": 1, "name_ar": "القاهرة", "name_en": "Cairo" },
    { "id": 2, "name_ar": "الإسكندرية", "name_en": "Alexandria" }
  ]
}
```

**للفرونت:** استخدمه في dropdown عند إنشاء/تعديل شركة. القيمة المرسلة = `governorate_id`.

---

## Super Admin — Companies

### 5. قائمة الشركات

```
GET /super-admin/companies?page=1
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Super Admin) |

**Query params:**

| Param | Type | Default | الوصف |
|-------|------|---------|-------|
| `page` | number | `1` | رقم الصفحة |

**Response `200`:**

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

**للفرونت:** استخدم `meta.last_page` لبناء pagination. `logo` جاهز للعرض مباشرة في `<img src>`.

---

### 6. إنشاء شركة

```
POST /super-admin/companies
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Super Admin) |
| **Content-Type** | `multipart/form-data` |

**Body (FormData):**

| Field | Type | Required | القيود |
|-------|------|----------|--------|
| `name` | string | ✅ | max 255 |
| `tax_number` | string | ✅ | فريد — يُستخدم لدخول الشركة |
| `password` | string | ✅ | min 8 أحرف |
| `governorate_id` | number | ✅ | ID من `/governorates` |
| `logo` | File | ❌ | jpg/jpeg/png/webp — max 2MB |
| `is_active` | boolean | ❌ | افتراضي `true` — أرسل `1` أو `0` |

**Response `201`:**

```json
{
  "message": "تم إنشاء الشركة بنجاح",
  "data": { /* Company object */ }
}
```

**Errors:**

| Status | السبب |
|--------|-------|
| `422` | validation — مثلاً tax_number مكرر |

**مثال FormData:**

```js
const form = new FormData();
form.append('name', 'شركة الأمل');
form.append('tax_number', 'TAX-2024-001');
form.append('password', 'Company@1234');
form.append('governorate_id', '1');
if (logoFile) form.append('logo', logoFile);
form.append('is_active', '1');

await axios.post('/super-admin/companies', form);
// لا تضيف Content-Type يدويًا — axios يحدده تلقائيًا مع boundary
```

---

### 7. تفاصيل شركة

```
GET /super-admin/companies/{id}
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Super Admin) |

**URL param:** `id` — معرّف الشركة

**Response `200`:**

```json
{
  "data": { /* Company object */ }
}
```

**Errors:** `404` — الشركة غير موجودة

---

### 8. تعديل شركة

```
POST /super-admin/companies/{id}
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Super Admin) |
| **Content-Type** | `multipart/form-data` |

> **ملاحظة:** التعديل بـ `POST` مش `PUT`/`PATCH` عشان دعم رفع الصور.

**Body (FormData):** نفس حقول الإنشاء — **كلها اختيارية** (أرسل اللي عايز تغيره بس).

**Response `200`:**

```json
{
  "message": "تم تحديث الشركة بنجاح",
  "data": { /* Company object محدّث */ }
}
```

**للفرونت:** لو بتغير الصورة بس، أرسل `logo` فقط. لو بتغير password أرسل `password` الجديد.

---

### 9. حذف شركة

```
DELETE /super-admin/companies/{id}
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Super Admin) |

**Response `200`:**

```json
{ "message": "تم حذف الشركة بنجاح" }
```

---

### 10. تفعيل / تعطيل شركة

```
PATCH /super-admin/companies/{id}/toggle-status
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Super Admin) |
| **Body** | لا يوجد |

**Response `200`:**

```json
{
  "message": "تم تعطيل الشركة",
  "is_active": false
}
```

**للفرونت:** استخدم `is_active` في الـ response لتحديث الـ UI مباشرة بدون re-fetch.

> الشركة المعطّلة (`is_active = false`) **مش هتقدر تسجل دخول** — هترجع `403`.

---

## Super Admin — Suppliers

### 11. قائمة الموردين

```
GET /super-admin/suppliers?page=1
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Super Admin) |

**Response `200`:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "مورد الأغذية",
      "description": "وصف المورد",
      "logo": "http://localhost:8000/storage/logos/suppliers/xxx.jpg",
      "products_count": 12,
      "created_at": "2026-05-21 10:00:00"
    }
  ],
  "meta": { "total": 5, "current_page": 1, "last_page": 1, "per_page": 15 }
}
```

---

### 12. إنشاء مورد

```
POST /super-admin/suppliers
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Super Admin) |
| **Content-Type** | `multipart/form-data` |

**Body (FormData):**

| Field | Type | Required | القيود |
|-------|------|----------|--------|
| `name` | string | ✅ | max 255 |
| `description` | string | ❌ | — |
| `logo` | File | ❌ | jpg/jpeg/png/webp — max 2MB |

**Response `201`:**

```json
{
  "message": "تم إنشاء المورد بنجاح",
  "data": { /* Supplier object */ }
}
```

---

### 13. تفاصيل مورد

```
GET /super-admin/suppliers/{id}
```

**Response `200`:**

```json
{
  "data": {
    "id": 1,
    "name": "مورد الأغذية",
    "description": "وصف",
    "logo": "...",
    "products_count": 12,
    "created_at": "..."
  }
}
```

---

### 14. تعديل مورد

```
POST /super-admin/suppliers/{id}
```

| | |
|---|---|
| **Content-Type** | `multipart/form-data` |
| **Body** | كل الحقول اختيارية |

**Response `200`:**

```json
{
  "message": "تم تحديث المورد بنجاح",
  "data": { /* Supplier object */ }
}
```

---

### 15. حذف مورد

```
DELETE /super-admin/suppliers/{id}
```

**Response `200`:**

```json
{ "message": "تم حذف المورد بنجاح" }
```

> **تحذير:** حذف المورد يحذف **كل منتجاته** تلقائيًا (cascade). اعرض confirmation dialog في الفرونت.

---

## Super Admin — Supplier Products

### 16. قائمة منتجات الموردين

```
GET /super-admin/supplier-products?page=1&supplier_id=1&is_active=1
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Super Admin) |

**Query params:**

| Param | Type | الوصف |
|-------|------|-------|
| `page` | number | رقم الصفحة |
| `supplier_id` | number | فلترة حسب مورد معين |
| `is_active` | `0` \| `1` | فلترة حسب الحالة |

**Response `200`:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "زيت زيتون",
      "description": "زيت بكر ممتاز",
      "image": "http://localhost:8000/products/supplier/xxx.jpg",
      "cash_price": "5000.00",
      "is_active": true,
      "installment_plans": [
        {
          "months": 3,
          "down_payment": "1000.00",
          "installment_amount": "1500.00"
        }
      ],
      "supplier": {
        "id": 1,
        "name": "مورد الأغذية",
        "description": null,
        "logo": null,
        "created_at": "..."
      },
      "created_at": "2026-05-21 10:00:00"
    }
  ],
  "meta": { "total": 30, "current_page": 1, "last_page": 2, "per_page": 15 }
}
```

**للفرونت:** `cash_price` وحقول التقسيط نوعها string — استخدم `parseFloat()` للعرض أو الحسابات.

---

### 17. إضافة منتج مورد

```
POST /super-admin/supplier-products
```

| | |
|---|---|
| **Content-Type** | `multipart/form-data` |

**Body (FormData أو JSON):**

| Field | Type | Required | القيود |
|-------|------|----------|--------|
| `name` | string | ✅ | max 255 |
| `cash_price` | number | ✅ | ≥ 0 — سعر الكاش |
| `supplier_id` | number | ✅ | ID مورد موجود |
| `description` | string | ❌ | — |
| `image` | File | ❌ | jpg/jpeg/png/webp — max 2MB |
| `is_active` | boolean | ❌ | افتراضي `true` |
| `installment_plans` | array أو JSON string | ❌ | خطط التقسيط (اختياري) |

**`installment_plans` — كل عنصر:**

| Field | Type | Required | القيود |
|-------|------|----------|--------|
| `months` | number | ✅ | `3, 6, 9, 12, 15, 18` — بدون تكرار |
| `down_payment` | number | ✅ | ≥ 0 — المقدم |
| `installment_amount` | number | ✅ | > 0 — قيمة القسط **الشهري** |

**مثال FormData:**

```js
formData.append('name', 'زيت زيتون');
formData.append('cash_price', '5000');
formData.append('supplier_id', '1');
formData.append('installment_plans', JSON.stringify([
  { months: 3, down_payment: 1000, installment_amount: 1500 },
  { months: 6, down_payment: 800, installment_amount: 800 },
]));
```

**Response `201`:**

```json
{
  "message": "تم إنشاء المنتج بنجاح",
  "data": { /* SupplierProduct object */ }
}
```

---

### 18. تفاصيل منتج مورد

```
GET /super-admin/supplier-products/{id}
```

**Response `200`:**

```json
{
  "data": { /* SupplierProduct object مع supplier */ }
}
```

---

### 19. تعديل منتج مورد

```
POST /super-admin/supplier-products/{id}
```

| | |
|---|---|
| **Content-Type** | `multipart/form-data` أو `application/json` |
| **Body** | كل الحقول اختيارية |

> إذا أُرسل `installment_plans` يتم **استبدال كل الخطط** بالقيم الجديدة. لإزالة كل الخطط أرسل `installment_plans: []`. إذا لم يُرسل الحقل تبقى الخطط القديمة كما هي.

**Response `200`:**

```json
{
  "message": "تم تحديث المنتج بنجاح",
  "data": { /* SupplierProduct object */ }
}
```

---

### 20. حذف منتج مورد

```
DELETE /super-admin/supplier-products/{id}
```

**Response `200`:**

```json
{ "message": "تم حذف المنتج بنجاح" }
```

---

### 21. تفعيل / إيقاف منتج مورد

```
PATCH /super-admin/supplier-products/{id}/toggle-status
```

| | |
|---|---|
| **Body** | لا يوجد |

**Response `200`:**

```json
{
  "message": "تم تعطيل المنتج",
  "is_active": false
}
```

> المنتج المعطّل **لا يظهر** للشركات في `/company/catalog/available`.

---

# Company

**Prefix:** `/api/company`

---

## Company — Auth

### 22. تسجيل الدخول

```
POST /company/login
```

| | |
|---|---|
| **Auth** | لا يحتاج token |
| **Content-Type** | `application/json` |

**Body:**

```json
{
  "tax_number": "TAX-2024-001",
  "password": "Company@1234"
}
```

| Field | Type | Required |
|-------|------|----------|
| `tax_number` | string | ✅ |
| `password` | string | ✅ |

**Response `200`:**

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

**Errors:**

| Status | Response | السبب |
|--------|----------|-------|
| `422` | `{ errors: { tax_number: [...] } }` | بيانات غلط |
| `403` | `{ "message": "حسابك موقوف. تواصل مع الإدارة." }` | `is_active = false` |

**للفرونت:** اعرض رسالة الـ `403` للمستخدم — مش validation error عادي.

---

### 23. تسجيل الخروج

```
POST /company/logout
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Company) |

**Response `200`:**

```json
{ "message": "تم تسجيل الخروج بنجاح" }
```

---

### 24. بياناتي

```
GET /company/me
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Company) |

**Response `200`:**

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

**للفرونت:** الفرق عن Super Admin `/me` — هنا البيانات داخل `data` wrapper.

---

## Company — Products

منتجات الشركة **الخاصة** (مش منتجات الموردين).

### 25. قائمة منتجاتي

```
GET /company/products?page=1
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Company) |

**Response `200`:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "منتج الشركة",
      "description": "وصف",
      "image": "http://localhost:8000/products/company/xxx.jpg",
      "cash_price": "5000.00",
      "is_active": true,
      "installment_plans": [
        {
          "months": 3,
          "down_payment": "1000.00",
          "installment_amount": "1500.00"
        },
        {
          "months": 6,
          "down_payment": "800.00",
          "installment_amount": "800.00"
        }
      ],
      "created_at": "2026-05-21 10:00:00"
    }
  ],
  "meta": { "total": 10, "current_page": 1, "last_page": 1, "per_page": 15 }
}
```

> يرجع منتجات الشركة المسجّلة دخولها **فقط** — مفيش فلتر إضافي محتاج.

---

### 26. إضافة منتج

```
POST /company/products
```

| | |
|---|---|
| **Content-Type** | `multipart/form-data` |

**Body (FormData أو JSON):**

| Field | Type | Required | القيود |
|-------|------|----------|--------|
| `name` | string | ✅ | max 255 |
| `cash_price` | number | ✅ | ≥ 0 — سعر الكاش |
| `description` | string | ❌ | — |
| `image` | File | ❌ | jpg/jpeg/png/webp — max 2MB |
| `is_active` | boolean | ❌ | افتراضي `true` |
| `installment_plans` | array أو JSON string | ❌ | خطط التقسيط (اختياري) |

**`installment_plans` — كل عنصر:**

| Field | Type | Required | القيود |
|-------|------|----------|--------|
| `months` | number | ✅ | `3` أو `6` أو `9` أو `12` أو `15` أو `18` — بدون تكرار |
| `down_payment` | number | ✅ | ≥ 0 — المقدم |
| `installment_amount` | number | ✅ | > 0 — قيمة القسط **الشهري** |

**مثال FormData:**

```js
formData.append('name', 'منتج الشركة');
formData.append('cash_price', '5000');
formData.append('installment_plans', JSON.stringify([
  { months: 3, down_payment: 1000, installment_amount: 1500 },
  { months: 6, down_payment: 800, installment_amount: 800 },
]));
```

**Response `201`:**

```json
{
  "message": "تم إضافة المنتج بنجاح",
  "data": { /* CompanyProduct object */ }
}
```

---

### 27. تفاصيل منتج

```
GET /company/products/{id}
```

**Response `200`:**

```json
{
  "data": { /* CompanyProduct object */ }
}
```

**Errors:**

| Status | السبب |
|--------|-------|
| `403` | المنتج مش تابع للشركة دي |
| `404` | المنتج غير موجود |

---

### 28. تعديل منتج

```
POST /company/products/{id}
```

| | |
|---|---|
| **Content-Type** | `multipart/form-data` أو `application/json` |
| **Body** | كل الحقول اختيارية |

> إذا أُرسل `installment_plans` يتم **استبدال كل الخطط** بالقيم الجديدة. لإزالة كل الخطط أرسل `installment_plans: []`. إذا لم يُرسل الحقل تبقى الخطط القديمة كما هي.

**Response `200`:**

```json
{
  "message": "تم تحديث المنتج بنجاح",
  "data": { /* CompanyProduct object */ }
}
```

---

### 29. حذف منتج

```
DELETE /company/products/{id}
```

**Response `200`:**

```json
{ "message": "تم حذف المنتج بنجاح" }
```

---

## Company — Catalog

الشركة تختار منتجات من كتالوج الموردين وتضيفها لمتجرها.

```
┌─────────────────────────────────────────────────────────┐
│  منتجات الموردين (Supplier Products)                    │
│       ↓ الشركة تختار                                    │
│  كتالوج الشركة (Catalog)  +  منتجات الشركة الخاصة       │
└─────────────────────────────────────────────────────────┘
```

---

### 30. المنتجات المتاحة للاختيار

```
GET /company/catalog/available?page=1&supplier_id=1
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Company) |

**Query params:**

| Param | Type | الوصف |
|-------|------|-------|
| `page` | number | رقم الصفحة |
| `supplier_id` | number | فلترة حسب مورد (اختياري) |

**Response `200`:**

```json
{
  "data": [ /* SupplierProduct[] — is_active = true فقط */ ],
  "meta": { "total": 20, "current_page": 1, "last_page": 2, "per_page": 15 }
}
```

**للفرونت:** استخدمه في شاشة "اختر منتجات للمتجر". قارن مع `/catalog/mine` لمعرفة المنتجات المضافة مسبقًا.

---

### 31. منتجاتي في الكتالوج

```
GET /company/catalog/mine?page=1
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Company) |

**Response `200`:**

```json
{
  "data": [ /* SupplierProduct[] اللي الشركة اختارتها */ ],
  "meta": { "total": 5, "current_page": 1, "last_page": 1, "per_page": 15 }
}
```

---

### 32. إضافة منتجات متعددة

```
POST /company/catalog/add
```

| | |
|---|---|
| **Content-Type** | `application/json` |

**Body:**

```json
{
  "product_ids": [1, 2, 3]
}
```

| Field | Type | Required |
|-------|------|----------|
| `product_ids` | number[] | ✅ — array فيه عنصر واحد على الأقل |

**Response `200`:**

```json
{ "message": "تم إضافة المنتجات إلى متجرك بنجاح" }
```

**سلوك مهم للفرونت:**

- المنتجات الموجودة مسبقًا **لا تتكرر** (idempotent)
- المنتجات غير المفعّلة (`is_active = false`) **يتم تجاهلها** بدون error
- مناسب لـ bulk selection (checkboxes)

**مثال:**

```js
const selectedIds = [1, 5, 12];
await axios.post('/company/catalog/add', { product_ids: selectedIds });
```

---

### 33. إزالة منتجات متعددة

```
POST /company/catalog/remove
```

| | |
|---|---|
| **Content-Type** | `application/json` |

**Body:**

```json
{
  "product_ids": [1, 2]
}
```

**Response `200`:**

```json
{ "message": "تم إزالة المنتجات من متجرك بنجاح" }
```

---

### 34. إضافة منتج واحد

```
POST /company/catalog/{id}
```

| | |
|---|---|
| **Content-Type** | `application/json` |

**URL param:** `{id}` — معرّف منتج المورد

**Body (مطلوب):**

```json
{
  "supplier_product_id": 5
}
```

| Field | Type | Required |
|-------|------|----------|
| `supplier_product_id` | number | ✅ |

> **مهم للفرونت:** الـ endpoint بيتوقع `supplier_product_id` في الـ body حتى لو موجود في الـ URL. أرسل الاتنين بنفس القيمة.

**Response `200`:**

```json
{ "message": "تم إضافة المنتج إلى متجرك بنجاح" }
```

**Errors:**

| Status | Response |
|--------|----------|
| `422` | `{ "message": "هذا المنتج غير متاح حاليًا" }` — المنتج معطّل |

**مثال:**

```js
const productId = 5;
await axios.post(`/company/catalog/${productId}`, {
  supplier_product_id: productId,
});
```

---

### 35. إزالة منتج واحد

```
DELETE /company/catalog/{id}
```

| | |
|---|---|
| **Auth** | ✅ Bearer token (Company) |
| **Body** | لا يوجد |

**URL param:** `{id}` — معرّف منتج المورد

**Response `200`:**

```json
{ "message": "تم إزالة المنتج من متجرك بنجاح" }
```

---

## Public

Endpoints عامة **بدون تسجيل دخول** — للمستخدم النهائي عند تصفح الشركات والمتاجر.

### Public — Governorates

```
GET /public/governorates
```

| | |
|---|---|
| **Auth** | لا يوجد |

**Response `200`:**

```json
{
  "data": [
    { "id": 1, "name_ar": "القاهرة", "name_en": "Cairo" }
  ]
}
```

**للفرونت:** استخدمه في dropdown فلتر المحافظات. القيمة = `governorate_id`.

---

### Public — Companies

#### قائمة الشركات حسب المحافظة

```
GET /public/companies?governorate_id={id}
```

| | |
|---|---|
| **Auth** | لا يوجد |
| **Query** | `governorate_id` (مطلوب)، `page` (اختياري) |

**Response `200`:** شركات نشطة فقط (`is_active = true`) — بدون `tax_number`.

```ts
interface PublicCompany {
  id: number;
  name: string;
  logo: string | null;
  governorate: Governorate;
  likes_count: number;       // إجمالي الإعجابات
  ratings_count: number;     // عدد التقييمات
  average_rating: number | null;  // متوسط التقييم (1-5)، null لو مفيش تقييمات
  is_liked?: boolean;        // موجود فقط لو العميل مسجل دخول
  my_rating?: number | null; // تقييم العميل الحالي (1-5)
}
```

```json
{
  "data": [
    {
      "id": 1,
      "name": "شركة المياه",
      "logo": "http://localhost:8000/storage/logos/companies/abc.png",
      "governorate": { "id": 1, "name_ar": "القاهرة", "name_en": "Cairo" },
      "likes_count": 42,
      "ratings_count": 15,
      "average_rating": 4.3
    }
  ],
  "meta": { "total": 5, "current_page": 1, "last_page": 1, "per_page": 15 }
}
```

> **اختياري:** أرسل `Authorization: Bearer {customer_token}` مع طلبات `/public/companies` لتحصل على `is_liked` و `my_rating` لكل شركة.

#### تفاصيل شركة

```
GET /public/companies/{id}
```

**Response `404`:** إذا الشركة غير نشطة.

---

### Public — Store Products

```
GET /public/companies/{id}/products?page=1
```

| | |
|---|---|
| **Auth** | لا يوجد |

يعرض منتجات المتجر = **منتجات الشركة الخاصة** + **منتجات الكتالوج** (من الموردين).

```ts
interface PublicStoreProduct {
  id: number;
  source: 'company' | 'catalog';
  name: string;
  description: string | null;
  image: string | null;
  cash_price: string;
  installment_plans: InstallmentPlan[];
  supplier: Supplier | null;  // موجود فقط لو source = catalog
  created_at: string;
}

interface InstallmentPlan {
  months: number;
  down_payment: string;
  installment_amount: string;
}
```

**للفرونت:** عند الطلب استخدم `source` كـ `product_type`:
- `company` → `product_type: "company_product"`
- `catalog` → `product_type: "supplier_product"`

---

## Customer

### Customer — Auth

#### 1. التحقق من رقم الهاتف

```
POST /customer/auth/check-phone
```

```json
{ "phone": "01285254756" }
```

**Response `200`:**

```json
{ "exists": true }
```

- `exists: true` → اعرض شاشة إدخال كلمة المرور
- `exists: false` → ابدأ تسجيل جديد (OTP)

---

#### 2. تسجيل الدخول (حساب موجود)

```
POST /customer/login
```

```json
{
  "phone": "01285254756",
  "password": "MyPassword123"
}
```

**Response `200`:**

```json
{
  "message": "تم تسجيل الدخول بنجاح",
  "token": "1|abc...",
  "customer": {
    "id": 1,
    "name": "كيرلس منير",
    "phone": "01285254756",
    "governorate": { "id": 1, "name_ar": "القاهرة", "name_en": "Cairo" }
  }
}
```

---

#### 3. طلب OTP (حساب جديد)

```
POST /customer/register/request-otp
```

```json
{ "phone": "01285254756" }
```

**Response `200`:**

```json
{
  "message": "تم إرسال رمز التحقق",
  "debug_otp": "123456"
}
```

> `debug_otp` يظهر فقط عند `APP_DEBUG=true` للتطوير.

---

#### 4. إنشاء حساب + تسجيل دخول

```
POST /customer/register/verify
```

```json
{
  "phone": "01285254756",
  "otp": "123456",
  "name": "كيرلس منير",
  "password": "MyPassword123",
  "password_confirmation": "MyPassword123",
  "governorate_id": 1
}
```

**Response `201`:** نفس شكل login — `token` + `customer`.

---

#### 5. بياناتي / خروج

```
GET  /customer/me      (Auth: Customer)
POST /customer/logout  (Auth: Customer)
```

---

### Customer — Orders

> **يتطلب تسجيل دخول** — عند الضغط على "شراء" بدون token، وجّه المستخدم لـ auth flow أولاً.

#### إنشاء طلب شراء (منتج واحد)

```
POST /customer/orders
```

```json
{
  "company_id": 1,
  "product_type": "company_product",
  "product_id": 5,
  "quantity": 1,
  "delivery_address": "القاهرة، مدينة نصر، شارع...",
  "notes": "اتصل قبل التوصيل"
}
```

| الحقل | القيم |
|-------|-------|
| `product_type` | `company_product` أو `supplier_product` |
| `product_id` | ID المنتج (حسب `source` من المتجر) |

**Response `201`:**

```json
{
  "message": "تم إنشاء الطلب بنجاح",
  "data": {
    "id": 1,
    "product_type": "company_product",
    "product_id": 5,
    "quantity": 1,
    "unit_price": "1500.00",
    "total_price": "1500.00",
    "delivery_address": "...",
    "notes": null,
    "status": "pending",
    "company": { "id": 1, "name": "...", "logo": "...", "governorate": {} },
    "product": { "id": 5, "source": "company", "name": "...", "cash_price": "1500.00" },
    "created_at": "2026-06-10 12:00:00"
  }
}
```

#### قائمة طلباتي / تفاصيل

```
GET /customer/orders
GET /customer/orders/{id}
```

---

### Customer — Maintenance Requests

> **يتطلب تسجيل دخول**

#### إنشاء طلب صيانة

```
POST /customer/maintenance-requests
Content-Type: multipart/form-data
```

| الحقل | النوع | مطلوب |
|-------|-------|-------|
| `company_id` | number | ✅ |
| `description` | string | ✅ |
| `address` | string | اختياري |
| `image` | file | اختياري |

**Response `201`:**

```json
{
  "message": "تم إرسال طلب الصيانة بنجاح",
  "data": {
    "id": 1,
    "description": "فلتر المياه بيسرب",
    "address": "القاهرة...",
    "image": null,
    "status": "pending",
    "company": { "id": 1, "name": "..." },
    "created_at": "2026-06-10 12:00:00"
  }
}
```

#### قائمة طلبات الصيانة / تفاصيل

```
GET /customer/maintenance-requests
GET /customer/maintenance-requests/{id}
```

**حالات الطلب:** `pending` | `in_progress` | `completed` | `cancelled`

---

### Customer — Company Likes & Ratings

> **يتطلب تسجيل دخول** — العميل يقدر يعمل like مرة واحدة ويقيّم مرة واحدة (يقدر يعدّل تقييمه).

#### إعجاب بشركة

```
POST /customer/companies/{id}/like
```

**Response `201`:**

```json
{
  "message": "تم تسجيل الإعجاب بنجاح",
  "data": {
    "id": 1,
    "name": "شركة المياه",
    "likes_count": 43,
    "ratings_count": 15,
    "average_rating": 4.3,
    "is_liked": true,
    "my_rating": 5
  }
}
```

**422:** إذا أعجب بالشركة من قبل.

#### إلغاء الإعجاب

```
DELETE /customer/companies/{id}/like
```

**422:** إذا لم يعجب بالشركة من قبل.

#### تقييم شركة

```
POST /customer/companies/{id}/rating
```

```json
{
  "rating": 5,
  "comment": "خدمة ممتازة وسريعة"
}
```

| Field | مطلوب | القيود |
|-------|-------|--------|
| `rating` | ✅ | 1–5 |
| `comment` | ❌ | max 1000 حرف |

**سلوك:** لو العميل قيّم من قبل → يتم **تحديث** التقييم (upsert).

**Response `200`:** يرجع بيانات الشركة مع `average_rating` و `ratings_count` المحدّثين.

#### حذف التقييم

```
DELETE /customer/companies/{id}/rating
```

---

## معالجة الأخطاء

| Status | المعنى | إجراء الفرونت |
|--------|--------|---------------|
| `200` | نجاح | — |
| `201` | تم الإنشاء | redirect أو أضف للقائمة |
| `401` | Token مش موجود/منتهي | امسح token → صفحة login |
| `403` | ممنوع | اعرض `response.data.message` |
| `404` | غير موجود | اعرض "العنصر غير موجود" |
| `422` | Validation | اعرض `response.data.errors` تحت كل field |
| `500` | خطأ سيرفر | اعرض رسالة عامة |

### مثال معالجة أخطاء

```js
try {
  await axios.post('/company/products', formData);
} catch (error) {
  const { status, data } = error.response;

  if (status === 422 && data.errors) {
    // عرض أخطاء تحت كل input
    setFieldErrors(data.errors);
  } else if (status === 403) {
    toast.error(data.message);
  } else if (status === 401) {
    logout();
  }
}
```

---

## ملاحظات مهمة للفرونت

### 1. JSON vs FormData

| النوع | Content-Type | Endpoints |
|-------|-------------|-----------|
| JSON | `application/json` | login، logout، me، catalog/add، catalog/remove، catalog/{id} (POST) |
| FormData | `multipart/form-data` | أي endpoint فيه رفع صورة (شركات، موردين، منتجات) |

### 2. التعديل = POST مش PUT

كل update endpoints تستخدم `POST` عشان رفع الصور. **لا تستخدم PUT/PATCH** للتعديل مع ملفات.

### 3. الصور جاهزة للعرض

حقول `logo` و `image` بترجع **URL كامل** — استخدمها مباشرة:

```jsx
<img src={product.image ?? '/placeholder.png'} alt={product.name} />
```

### 4. السعر string

`cash_price` و `down_payment` و `installment_amount` بترجع كـ `"150.00"` مش `150`. للعرض:

```js
const formatted = parseFloat(product.cash_price).toFixed(2) + ' ج.م';
```

> منتجات الشركة ومنتجات الموردين: `cash_price` إلزامي، `installment_plans` اختياري.

### 5. Pagination

```js
const fetchPage = (page) => axios.get(`/super-admin/companies?page=${page}`);

// بناء pagination
const { current_page, last_page, total } = response.data.meta;
```

### 6. دورين منفصلين

احفظ token منفصل لكل دور لو عندك تطبيقين:

```js
localStorage.setItem('super_admin_token', token);  // لوحة الإدارة
localStorage.setItem('company_token', token);       // تطبيق الشركة
localStorage.setItem('customer_token', token);      // تطبيق المستخدم النهائي
```

### 7. رسائل النجاح

معظم العمليات بترجع `message` بالعربي — اعرضها في toast:

```js
toast.success(response.data.message);
```

### 8. Postman Collection

ملف **`Watafl.postman_collection.json`** في جذر المشروع — مجموعة **كاملة** تشمل:

| المجلد | المحتوى |
|--------|---------|
| **Public** | محافظات، شركات، منتجات المتجر (بدون auth) |
| **Customer** | تسجيل/دخول OTP، طلبات شراء، طلبات صيانة |
| **Super Admin** | شركات، موردين، منتجات، محفظة |
| **Company** | منتجات، كتالوج، تقسيط |

> ملف `Watafl_New_Features.postman_collection.json` للميزات القديمة فقط — استخدم المجموعة الرئيسية.

---

## فهرس سريع

| # | Method | Endpoint | Auth |
|---|--------|----------|------|
| 1 | POST | `/super-admin/login` | — |
| 2 | POST | `/super-admin/logout` | Super Admin |
| 3 | GET | `/super-admin/me` | Super Admin |
| 4 | GET | `/super-admin/governorates` | Super Admin |
| 5 | GET | `/super-admin/companies` | Super Admin |
| 6 | POST | `/super-admin/companies` | Super Admin |
| 7 | GET | `/super-admin/companies/{id}` | Super Admin |
| 8 | POST | `/super-admin/companies/{id}` | Super Admin |
| 9 | DELETE | `/super-admin/companies/{id}` | Super Admin |
| 10 | PATCH | `/super-admin/companies/{id}/toggle-status` | Super Admin |
| 11 | GET | `/super-admin/suppliers` | Super Admin |
| 12 | POST | `/super-admin/suppliers` | Super Admin |
| 13 | GET | `/super-admin/suppliers/{id}` | Super Admin |
| 14 | POST | `/super-admin/suppliers/{id}` | Super Admin |
| 15 | DELETE | `/super-admin/suppliers/{id}` | Super Admin |
| 16 | GET | `/super-admin/supplier-products` | Super Admin |
| 17 | POST | `/super-admin/supplier-products` | Super Admin |
| 18 | GET | `/super-admin/supplier-products/{id}` | Super Admin |
| 19 | POST | `/super-admin/supplier-products/{id}` | Super Admin |
| 20 | DELETE | `/super-admin/supplier-products/{id}` | Super Admin |
| 21 | PATCH | `/super-admin/supplier-products/{id}/toggle-status` | Super Admin |
| 22 | POST | `/company/login` | — |
| 23 | POST | `/company/logout` | Company |
| 24 | GET | `/company/me` | Company |
| 25 | GET | `/company/products` | Company |
| 26 | POST | `/company/products` | Company |
| 27 | GET | `/company/products/{id}` | Company |
| 28 | POST | `/company/products/{id}` | Company |
| 29 | DELETE | `/company/products/{id}` | Company |
| 30 | GET | `/company/catalog/available` | Company |
| 31 | GET | `/company/catalog/mine` | Company |
| 32 | POST | `/company/catalog/add` | Company |
| 33 | POST | `/company/catalog/remove` | Company |
| 34 | POST | `/company/catalog/{id}` | Company |
| 35 | DELETE | `/company/catalog/{id}` | Company |
| 36 | GET | `/public/governorates` | — |
| 37 | GET | `/public/companies` | — |
| 38 | GET | `/public/companies/{id}` | — |
| 39 | GET | `/public/companies/{id}/products` | — |
| 40 | POST | `/customer/auth/check-phone` | — |
| 41 | POST | `/customer/login` | — |
| 42 | POST | `/customer/register/request-otp` | — |
| 43 | POST | `/customer/register/verify` | — |
| 44 | POST | `/customer/logout` | Customer |
| 45 | GET | `/customer/me` | Customer |
| 46 | GET | `/customer/orders` | Customer |
| 47 | POST | `/customer/orders` | Customer |
| 48 | GET | `/customer/orders/{id}` | Customer |
| 49 | GET | `/customer/maintenance-requests` | Customer |
| 50 | POST | `/customer/maintenance-requests` | Customer |
| 51 | GET | `/customer/maintenance-requests/{id}` | Customer |
| 52 | POST | `/customer/companies/{id}/like` | Customer |
| 53 | DELETE | `/customer/companies/{id}/like` | Customer |
| 54 | POST | `/customer/companies/{id}/rating` | Customer |
| 55 | DELETE | `/customer/companies/{id}/rating` | Customer |
