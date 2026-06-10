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

النظام فيه **دورين منفصلين** — كل دور له token خاص ولا يمكن استخدام token دور في endpoints الدور الآخر.

| الدور | Login endpoint | Token ability |
|-------|----------------|---------------|
| Super Admin | `POST /super-admin/login` | `role:super-admin` |
| Company | `POST /company/login` | `role:company` |

**Flow للفرونت:**

```
1. POST /login  →  احفظ data.token
2. كل request محمي  →  Header: Authorization: Bearer {token}
3. POST /logout  →  احذف الـ token من الـ storage
4. عند 401  →  وجّه المستخدم لصفحة login
5. عند 403  →  اعرض رسالة الخطأ (حساب موقوف / دور غلط)
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
  price: string;               // "150.00" — string مش number
  is_active: boolean;
  supplier: Supplier;          // موجود لما يكون loaded
  created_at: string;
}
```

---

### CompanyProduct

```ts
interface CompanyProduct {
  id: number;
  name: string;
  description: string | null;
  image: string | null;
  price: string;
  is_active: boolean;
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
      "image": "http://localhost:8000/storage/products/supplier/xxx.jpg",
      "price": "150.00",
      "is_active": true,
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

**للفرونت:** `price` نوعه string — استخدم `parseFloat(price)` للعرض أو الحسابات.

---

### 17. إضافة منتج مورد

```
POST /super-admin/supplier-products
```

| | |
|---|---|
| **Content-Type** | `multipart/form-data` |

**Body (FormData):**

| Field | Type | Required | القيود |
|-------|------|----------|--------|
| `name` | string | ✅ | max 255 |
| `price` | number | ✅ | ≥ 0 |
| `supplier_id` | number | ✅ | ID مورد موجود |
| `description` | string | ❌ | — |
| `image` | File | ❌ | jpg/jpeg/png/webp — max 2MB |
| `is_active` | boolean | ❌ | افتراضي `true` |

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
| **Content-Type** | `multipart/form-data` |
| **Body** | كل الحقول اختيارية |

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
      "image": "http://localhost:8000/storage/products/company/xxx.jpg",
      "price": "99.99",
      "is_active": true,
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

**Body (FormData):**

| Field | Type | Required | القيود |
|-------|------|----------|--------|
| `name` | string | ✅ | max 255 |
| `price` | number | ✅ | ≥ 0 |
| `description` | string | ❌ | — |
| `image` | File | ❌ | jpg/jpeg/png/webp — max 2MB |
| `is_active` | boolean | ❌ | افتراضي `true` |

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
| **Content-Type** | `multipart/form-data` |
| **Body** | كل الحقول اختيارية |

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

`price` بيرجع كـ `"150.00"` مش `150`. للعرض:

```js
const formatted = parseFloat(product.price).toFixed(2) + ' ج.م';
```

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
```

### 7. رسائل النجاح

معظم العمليات بترجع `message` بالعربي — اعرضها في toast:

```js
toast.success(response.data.message);
```

### 8. Postman Collection

ملف `Watafl.postman_collection.json` في جذر المشروع — فيه كل الـ endpoints جاهزة للاختبار.

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
