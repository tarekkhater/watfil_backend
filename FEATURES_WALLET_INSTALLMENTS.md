# توثيق الميزات الجديدة — المحفظة وخطط التقسيط

> يغطي هذا الملف التعديلات التي أُضيفت في المشروع: **محفظة الشركة** و**نظام التقسيط لمنتجات الشركة**.  
> لا يشمل تعديلات تخزين الصور.

---

## معلومات عامة

| البند | القيمة |
|-------|--------|
| **Base URL** | `http://localhost:8000/api` |
| **المصادقة** | Laravel Sanctum — `Authorization: Bearer {token}` |
| **صيغة المبالغ** | تُرجع كـ string مثل `"5000.00"` |

### الأدوار

| الدور | Prefix | الاستخدام |
|-------|--------|-----------|
| Super Admin | `/super-admin/...` | إدارة محفظة الشركات |
| Company | `/company/...` | إدارة منتجات الشركة مع التقسيط |

---

## الجزء الأول: محفظة الشركة (Company Wallet)

### الفكرة

كل شركة لها **محفظة رصيد** (`wallet_balance`):

- عند **إنشاء شركة جديدة** يبدأ الرصيد تلقائياً بـ `0`
- **Super Admin فقط** يستطيع عرض الرصيد وتعديله
- الشركة ترى رصيدها في `GET /company/me` وفي بيانات الشركة عند Super Admin

### الحقل في الـ Response

```json
{
  "id": 1,
  "name": "شركة الأمل",
  "tax_number": "TAX-2024-001",
  "is_active": true,
  "wallet_balance": "5000.00",
  "logo": "http://localhost:8000/logos/companies/xxx.jpg",
  "governorate": { "id": 1, "name_ar": "القاهرة", "name_en": "Cairo" },
  "created_at": "2026-05-21 10:00:00"
}
```

> `wallet_balance` يظهر في: قائمة الشركات، تفاصيل شركة، و`/company/me`.

---

### 1. عرض رصيد محفظة شركة

```
GET /super-admin/companies/{company_id}/wallet
```

| | |
|---|---|
| **Auth** | Super Admin Bearer token |
| **Path** | `company_id` — معرّف الشركة |

**Response `200`:**

```json
{
  "data": {
    "company_id": 1,
    "company_name": "شركة الأمل",
    "wallet_balance": "5000.00"
  }
}
```

---

### 2. تعيين رصيد المحفظة (تعديل مباشر)

```
PATCH /super-admin/companies/{company_id}/wallet
```

| | |
|---|---|
| **Auth** | Super Admin Bearer token |
| **Content-Type** | `application/json` |

**Body:**

```json
{
  "wallet_balance": 5000.00
}
```

| Field | Type | Required | القيود |
|-------|------|----------|--------|
| `wallet_balance` | number | ✅ | ≥ 0 |

**Response `200`:**

```json
{
  "message": "تم تحديث رصيد المحفظة بنجاح",
  "data": {
    "id": 1,
    "name": "شركة الأمل",
    "wallet_balance": "5000.00"
  }
}
```

**أخطاء شائعة:**

| Status | السبب |
|--------|-------|
| `422` | `wallet_balance` ناقص أو سالب |
| `404` | الشركة غير موجودة |
| `401` | Token غير صالح |

---

### 3. إضافة أو خصم من المحفظة

```
POST /super-admin/companies/{company_id}/wallet/adjust
```

| | |
|---|---|
| **Auth** | Super Admin Bearer token |
| **Content-Type** | `application/json` |

**Body — إضافة رصيد:**

```json
{
  "amount": 250.00,
  "type": "credit"
}
```

**Body — خصم رصيد:**

```json
{
  "amount": 100.00,
  "type": "debit"
}
```

| Field | Type | Required | القيود |
|-------|------|----------|--------|
| `amount` | number | ✅ | > 0 |
| `type` | string | ✅ | `credit` = إضافة — `debit` = خصم |

**Response `200` (إضافة):**

```json
{
  "message": "تم إضافة الرصيد بنجاح",
  "data": { "wallet_balance": "5250.00" }
}
```

**Response `200` (خصم):**

```json
{
  "message": "تم خصم الرصيد بنجاح",
  "data": { "wallet_balance": "4900.00" }
}
```

**Response `422` (رصيد غير كافٍ):**

```json
{
  "message": "رصيد المحفظة غير كافٍ"
}
```

---

### ملخص Endpoints المحفظة

| # | Method | Endpoint | الوصف |
|---|--------|----------|-------|
| 1 | GET | `/super-admin/companies/{id}/wallet` | عرض الرصيد |
| 2 | PATCH | `/super-admin/companies/{id}/wallet` | تعيين رصيد محدد |
| 3 | POST | `/super-admin/companies/{id}/wallet/adjust` | إضافة أو خصم |

---

## الجزء الثاني: نظام التقسيط لمنتجات الشركة

### الفكرة

عند إضافة منتج، صاحب الشركة يحدد:

1. **سعر الكاش** (`cash_price`) — **إلزامي**
2. **خطط التقسيط** (`installment_plans`) — **اختياري**

كل خطة تقسيط تحتوي:

| الحقل | الوصف |
|-------|-------|
| `months` | مدة التقسيط بالأشهر |
| `down_payment` | المقدم |
| `installment_amount` | قيمة **القسط الشهري** |

### المدد المسموحة

`3` — `6` — `9` — `12` — `15` — `18` شهر

- لا يمكن تكرار نفس المدة لنفس المنتج
- المنتج يمكن أن يكون كاش فقط بدون أي خطة تقسيط
- الإجمالي **لا يُحسب تلقائياً** — الشركة تحدد المقدم والقسط يدوياً

---

### هياكل البيانات (TypeScript)

```ts
interface CompanyProductInstallmentPlan {
  months: number;              // 3 | 6 | 9 | 12 | 15 | 18
  down_payment: string;        // "1000.00"
  installment_amount: string;  // "500.00" — قيمة القسط الشهري
}

interface CompanyProduct {
  id: number;
  name: string;
  description: string | null;
  image: string | null;
  cash_price: string;
  is_active: boolean;
  installment_plans: CompanyProductInstallmentPlan[];
  created_at: string;
}
```

> **تغيير مهم:** الحقل القديم `price` أصبح `cash_price`.

---

### 4. قائمة منتجات الشركة

```
GET /company/products?page=1
```

| | |
|---|---|
| **Auth** | Company Bearer token |

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
  "meta": {
    "total": 10,
    "current_page": 1,
    "last_page": 1,
    "per_page": 15
  }
}
```

---

### 5. إضافة منتج (كاش + تقسيط)

```
POST /company/products
```

| | |
|---|---|
| **Auth** | Company Bearer token |
| **Content-Type** | `multipart/form-data` أو `application/json` |

**Body (JSON):**

```json
{
  "name": "منتج الشركة",
  "cash_price": 5000.00,
  "description": "وصف المنتج",
  "is_active": true,
  "installment_plans": [
    { "months": 3, "down_payment": 1000.00, "installment_amount": 1500.00 },
    { "months": 6, "down_payment": 800.00, "installment_amount": 800.00 }
  ]
}
```

**Body (FormData):**

| Field | Type | Required | القيود |
|-------|------|----------|--------|
| `name` | string | ✅ | max 255 |
| `cash_price` | number | ✅ | ≥ 0 |
| `description` | string | ❌ | — |
| `image` | File | ❌ | jpg/jpeg/png/webp — max 2MB |
| `is_active` | boolean | ❌ | افتراضي `true` |
| `installment_plans` | JSON string | ❌ | انظر الجدول أدناه |

**حقول كل عنصر في `installment_plans`:**

| Field | Type | Required | القيود |
|-------|------|----------|--------|
| `months` | number | ✅ | `3, 6, 9, 12, 15, 18` — بدون تكرار |
| `down_payment` | number | ✅ | ≥ 0 |
| `installment_amount` | number | ✅ | > 0 |

**مثال FormData (JavaScript):**

```js
const formData = new FormData();
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

### 6. تفاصيل منتج

```
GET /company/products/{id}
```

| | |
|---|---|
| **Auth** | Company Bearer token |

**Response `200`:** نفس شكل `CompanyProduct` أعلاه.

**Errors:**

| Status | السبب |
|--------|-------|
| `403` | المنتج لا يخص الشركة المسجّلة |
| `404` | المنتج غير موجود |

---

### 7. تعديل منتج

```
POST /company/products/{id}
```

| | |
|---|---|
| **Auth** | Company Bearer token |
| **Content-Type** | `multipart/form-data` أو `application/json` |
| **Body** | كل الحقول اختيارية |

**سلوك `installment_plans` عند التعديل:**

| الحالة | النتيجة |
|--------|---------|
| لم يُرسل `installment_plans` | تبقى الخطط القديمة كما هي |
| أُرسل `installment_plans: []` | تُحذف كل خطط التقسيط |
| أُرسل مصفوفة جديدة | **استبدال كامل** للخطط القديمة |

**Response `200`:**

```json
{
  "message": "تم تحديث المنتج بنجاح",
  "data": { /* CompanyProduct object */ }
}
```

---

### 8. حذف منتج

```
DELETE /company/products/{id}
```

| | |
|---|---|
| **Auth** | Company Bearer token |

**Response `200`:**

```json
{ "message": "تم حذف المنتج بنجاح" }
```

> حذف المنتج يحذف خطط التقسيط المرتبطة به تلقائياً (cascade).

---

### ملخص Endpoints منتجات الشركة (المحدّثة)

| # | Method | Endpoint | الوصف |
|---|--------|----------|-------|
| 4 | GET | `/company/products` | قائمة المنتجات مع خطط التقسيط |
| 5 | POST | `/company/products` | إضافة منتج (كاش + تقسيط اختياري) |
| 6 | GET | `/company/products/{id}` | تفاصيل منتج |
| 7 | POST | `/company/products/{id}` | تعديل منتج و/أو خطط التقسيط |
| 8 | DELETE | `/company/products/{id}` | حذف منتج |

---

## الجزء الثالث: نظام التقسيط لمنتجات الموردين (Super Admin)

نفس نظام منتجات الشركة، لكن يُدار من **Super Admin** عبر `/super-admin/supplier-products`.

- `cash_price` إلزامي
- `installment_plans` اختياري
- المدد: `3, 6, 9, 12, 15, 18` شهر
- الشركات ترى خطط التقسيط في كتالوج الموردين (`/company/catalog/...`)

### 9. قائمة منتجات الموردين

```
GET /super-admin/supplier-products?page=1&supplier_id=1&is_active=1
```

**Response:** كل منتج يتضمن `cash_price` و `installment_plans[]`.

### 10. إضافة منتج مورد

```
POST /super-admin/supplier-products
```

**Body (JSON):**

```json
{
  "name": "زيت زيتون",
  "cash_price": 5000.00,
  "supplier_id": 1,
  "description": "زيت بكر ممتاز",
  "installment_plans": [
    { "months": 3, "down_payment": 1000.00, "installment_amount": 1500.00 },
    { "months": 12, "down_payment": 500.00, "installment_amount": 450.00 }
  ]
}
```

### 11. تفاصيل / تعديل / حذف منتج مورد

| Method | Endpoint | ملاحظات |
|--------|----------|---------|
| GET | `/super-admin/supplier-products/{id}` | تفاصيل مع التقسيط |
| POST | `/super-admin/supplier-products/{id}` | تعديل — نفس سلوك `installment_plans` |
| DELETE | `/super-admin/supplier-products/{id}` | حذف مع cascade للخطط |
| PATCH | `/super-admin/supplier-products/{id}/toggle-status` | تفعيل/تعطيل |

### ملخص Endpoints منتجات الموردين

| # | Method | Endpoint | الوصف |
|---|--------|----------|-------|
| 9 | GET | `/super-admin/supplier-products` | قائمة المنتجات مع التقسيط |
| 10 | POST | `/super-admin/supplier-products` | إضافة منتج (كاش + تقسيط) |
| 11 | GET | `/super-admin/supplier-products/{id}` | تفاصيل منتج |
| 12 | POST | `/super-admin/supplier-products/{id}` | تعديل منتج و/أو خطط |
| 13 | DELETE | `/super-admin/supplier-products/{id}` | حذف منتج |
| 14 | PATCH | `/super-admin/supplier-products/{id}/toggle-status` | تفعيل/تعطيل |

---

## قاعدة البيانات

### جدول `companies`

| العمود | النوع | ملاحظات |
|--------|-------|---------|
| `wallet_balance` | `decimal(12,2)` | افتراضي `0` |

### جدول `company_products`

| التغيير | التفاصيل |
|---------|----------|
| `price` → `cash_price` | سعر الكاش |

### جدول `company_product_installment_plans` (جديد)

| العمود | النوع | ملاحظات |
|--------|-------|---------|
| `company_product_id` | FK | cascade on delete |
| `months` | tinyint | 3, 6, 9, 12, 15, 18 |
| `down_payment` | decimal(10,2) | المقدم |
| `installment_amount` | decimal(10,2) | القسط الشهري |
| unique | `(company_product_id, months)` | منع تكرار المدة |

### جدول `supplier_products`

| التغيير | التفاصيل |
|---------|----------|
| `price` → `cash_price` | سعر الكاش |

### جدول `supplier_product_installment_plans` (جديد)

| العمود | النوع | ملاحظات |
|--------|-------|---------|
| `supplier_product_id` | FK | cascade on delete |
| `months` | tinyint | 3, 6, 9, 12, 15, 18 |
| `down_payment` | decimal(10,2) | المقدم |
| `installment_amount` | decimal(10,2) | القسط الشهري |
| unique | `(supplier_product_id, months)` | منع تكرار المدة |

### تشغيل الـ Migrations

```bash
php artisan migrate
```

---

## ملاحظات للفرونت

### المبالغ string

```js
const cash = parseFloat(product.cash_price).toFixed(2);
const monthly = parseFloat(plan.installment_amount).toFixed(2);
```

### عرض خطة التقسيط

```js
// مثال: 3 شهور — مقدم 1000 — قسط شهري 1500
`${plan.months} شهور | مقدم: ${plan.down_payment} | قسط: ${plan.installment_amount}/شهر`
```

### أخطاء Validation (`422`)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "cash_price": ["سعر الكاش مطلوب"],
    "installment_plans.0.months": ["مدة التقسيط غير مسموحة"]
  }
}
```

---

## Postman Collection

ملف المجموعة الجاهز للاستيراد:

```
Watafl_New_Features.postman_collection.json
```

يحتوي على كل الـ endpoints أعلاه مع أمثلة جاهزة وتوثيق داخل كل request.
