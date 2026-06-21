# دليل مشروع Watfil Backend — الحالة الحالية والـ APIs والسيناريوهات

> **التاريخ:** 2026-06-15  
> **النطاق:** Backend API فقط (Laravel 12 + Sanctum)  
> **Base URL:** `http://localhost:8000/api`  
> **Postman:** `Watfil_API_Complete.postman_collection.json`

---

## المحتويات

1. [نظرة عامة](#1-نظرة-عامة)
2. [المصادقة](#2-المصادقة)
3. [جداول قاعدة البيانات](#3-جداول-قاعدة-البيانات)
4. [قواعد الاستجابة](#4-قواعد-الاستجابة)
5. [Super Admin — Endpoints](#5-super-admin--endpoints)
6. [Company — Endpoints](#6-company--endpoints)
7. [السيناريوهات والفلو](#7-السيناريوهات-والفلو)
8. [ما لم يُنفّذ بعد](#8-ما-لم-ينفّذ-بعد)

---

## 1. نظرة عامة

| المجال | الحالة |
|--------|--------|
| مصادقة Super Admin / Company | مكتمل |
| CRUD شركات / موردين / منتجات | مكتمل |
| كتالوج الموردين للشركة | مكتمل |
| خطط التقسيط على مستوى المنتج | مكتمل |
| المحافظات | مكتمل (قراءة فقط) |
| **Finance Core** (Ledger + عمولات + سحب) | **مكتمل** |
| الطلبات / العملاء / إحالات / إعلانات | غير منفّذ |

---

## 2. المصادقة

| الدور | تسجيل الدخول | Token ability |
|-------|--------------|---------------|
| Super Admin | `email` + `password` | `role:super-admin` |
| Company | `tax_number` + `password` | `role:company` |

**Headers:**
```
Accept: application/json
Authorization: Bearer {token}
```

**بعد Seed:**
- Super Admin: `admin@watafl.com` / `Admin@1234`

---

## 3. جداول قاعدة البيانات

**أساسية:** `super_admins`, `companies`, `governorates`, `suppliers`, `supplier_products`, `supplier_product_installment_plans`, `company_products`, `company_product_installment_plans`, `company_catalog`

**مالية:** `wallet_transactions`, `wallet_transaction_meta`, `commission_rules`, `commission_events`, `withdrawal_requests`, `withdrawal_audits`

**مدد التقسيط:** 3, 6, 9, 12, 15, 18 شهر

---

## 4. قواعد الاستجابة

**قوائم:** `{ "data": [...], "meta": { total, current_page, last_page, per_page } }`  
**طفرات:** `{ "message": "...", "data": {...} }`  
**أخطاء:** `{ "message": "...", "errors": { "field": ["..."] } }` — HTTP 422

---

## 5. Super Admin — Endpoints

> Prefix: `/api/super-admin` — كلها محمية ما عدا `login`

### Auth
| # | Method | Path | الوصف |
|---|--------|------|-------|
| 1 | POST | `/login` | تسجيل دخول |
| 2 | POST | `/logout` | خروج |
| 3 | GET | `/me` | بياناتي |

### Governorates
| # | Method | Path | الوصف |
|---|--------|------|-------|
| 4 | GET | `/governorates` | قائمة المحافظات |

### Companies
| # | Method | Path | الوصف |
|---|--------|------|-------|
| 5 | GET | `/companies` | قائمة |
| 6 | POST | `/companies` | إنشاء (multipart للـ logo) |
| 7 | GET | `/companies/{id}` | تفاصيل |
| 8 | POST | `/companies/{id}` | تعديل |
| 9 | DELETE | `/companies/{id}` | حذف |
| 10 | PATCH | `/companies/{id}/toggle-status` | تفعيل/إيقاف |

**إنشاء شركة — Body:**
```json
{
  "name": "شركة المثال",
  "tax_number": "123456789",
  "password": "Company@1234",
  "governorate_id": 1,
  "is_active": true
}
```

### Company Wallet
| # | Method | Path | الوصف |
|---|--------|------|-------|
| 11 | GET | `/companies/{id}/wallet` | رصيد + آخر 5 حركات |
| 12 | PATCH | `/companies/{id}/wallet` | تعيين رصيد مباشر |
| 13 | POST | `/companies/{id}/wallet/adjust` | إضافة/خصم |
| 14 | GET | `/companies/{id}/wallet/transactions` | كشف حركات |

**Adjust Body:**
```json
{
  "amount": 200,
  "type": "credit",
  "reason": "شحن",
  "idempotency_key": "adj-001"
}
```

### Finance
| # | Method | Path | الوصف |
|---|--------|------|-------|
| 15 | GET | `/finance/commissions/summary` | ملخص العمولات (`from`, `to`) |
| 16 | GET | `/finance/withdrawal-requests` | طلبات السحب (`status`, `company_id`) |
| 17 | PATCH | `/finance/withdrawal-requests/{id}/approve` | اعتماد |
| 18 | PATCH | `/finance/withdrawal-requests/{id}/reject` | رفض + إرجاع رصيد |
| 19 | PATCH | `/finance/withdrawal-requests/{id}/pay` | تسجيل دفع |

### Suppliers
| # | Method | Path | الوصف |
|---|--------|------|-------|
| 20 | GET | `/suppliers` | قائمة |
| 21 | POST | `/suppliers` | إنشاء |
| 22 | GET | `/suppliers/{id}` | تفاصيل |
| 23 | POST | `/suppliers/{id}` | تعديل |
| 24 | DELETE | `/suppliers/{id}` | حذف |

### Supplier Products
| # | Method | Path | الوصف |
|---|--------|------|-------|
| 25 | GET | `/supplier-products` | قائمة (`supplier_id`, `is_active`) |
| 26 | POST | `/supplier-products` | إنشاء |
| 27 | GET | `/supplier-products/{id}` | تفاصيل |
| 28 | POST | `/supplier-products/{id}` | تعديل |
| 29 | DELETE | `/supplier-products/{id}` | حذف |
| 30 | PATCH | `/supplier-products/{id}/toggle-status` | تفعيل/إيقاف |

**منتج مع تقسيط:**
```json
{
  "name": "فلتر مياه",
  "cash_price": 5000,
  "supplier_id": 1,
  "is_active": true,
  "installment_plans": [
    { "months": 6, "down_payment": 500, "installment_amount": 800 }
  ]
}
```

---

## 6. Company — Endpoints

> Prefix: `/api/company` — كلها محمية ما عدا `login`

### Auth
| # | Method | Path | الوصف |
|---|--------|------|-------|
| 31 | POST | `/login` | تسجيل دخول (`tax_number`, `password`) |
| 32 | POST | `/logout` | خروج |
| 33 | GET | `/me` | بياناتي + `wallet_balance` |

### Products (منتجات الشركة الخاصة)
| # | Method | Path | الوصف |
|---|--------|------|-------|
| 34 | GET | `/products` | قائمة |
| 35 | POST | `/products` | إنشاء |
| 36 | GET | `/products/{id}` | تفاصيل |
| 37 | POST | `/products/{id}` | تعديل |
| 38 | DELETE | `/products/{id}` | حذف |

### Catalog (منتجات الموردين)
| # | Method | Path | الوصف |
|---|--------|------|-------|
| 39 | GET | `/catalog/available` | المتاح من الموردين |
| 40 | GET | `/catalog/mine` | كتالوجي |
| 41 | POST | `/catalog/add` | إضافة متعددة `{ "product_ids": [1,2] }` |
| 42 | POST | `/catalog/remove` | إزالة متعددة |
| 43 | POST | `/catalog/{supplierProduct}` | إضافة واحد |
| 44 | DELETE | `/catalog/{supplierProduct}` | إزالة واحد |

### Finance
| # | Method | Path | الوصف |
|---|--------|------|-------|
| 45 | GET | `/wallet/transactions` | كشف حركاتي |
| 46 | POST | `/wallet/withdrawals` | طلب سحب |

**طلب سحب:**
```json
{ "amount": 200, "idempotency_key": "wd-001" }
```
- الحد الأدنى: 100 جنيه
- يحجز الرصيد فوراً (`withdrawal_hold`)
- ينتظر موافقة الأدمن

---

## 7. السيناريوهات والفلو

### سيناريو 1: إعداد المنصة من الصفر

```
1. POST super-admin/login
2. GET  super-admin/governorates          → governorate_id
3. POST super-admin/companies             → شركة جديدة
4. POST super-admin/suppliers
5. POST super-admin/supplier-products
6. POST super-admin/companies/{id}/wallet/adjust  → شحن محفظة
```

### سيناريو 2: الشركة تبني متجرها

```
1. POST company/login
2. GET  company/catalog/available
3. POST company/catalog/add
4. GET  company/catalog/mine
5. POST company/products                  → منتجات خاصة (اختياري)
```

### سيناريو 3: دورة السحب الكاملة

```
رصيد 500 → طلب سحب 200 → رصيد 300 (pending)
    → Admin approve → approved
    → Admin pay (payout_reference) → paid
```

**مسار الرفض:**
```
طلب 200 → رصيد 300 → Admin reject → رصيد 500 (rejected)
```

| المرحلة | حركة محفظة |
|---------|------------|
| طلب سحب | debit — `withdrawal_hold` |
| رفض | credit — `withdrawal_release` |
| اعتماد/دفع | لا حركة جديدة |

### سيناريو 4: فئات Ledger

| category | direction | الوصف |
|----------|-----------|-------|
| `manual_adjustment` | credit/debit | تعديل إداري |
| `manual_set_balance` | credit/debit | تعيين رصيد |
| `commission` | debit | عمولة |
| `withdrawal_hold` | debit | حجز سحب |
| `withdrawal_release` | credit | إرجاع سحب مرفوض |

### حالات طلب السحب

`pending` → `approved` → `paid`  
أو `pending`/`approved` → `rejected`

---

## 8. ما لم يُنفّذ بعد

- العملاء (Customers) والطلبات (Orders)
- عقود التقسيط التشغيلية
- الإحالات والنقاط
- الشكاوى وSLA (72 ساعة)
- الإعلانات والحملات
- المدن والتغطية الجغرافية
- واجهة المتجر العامة
- تقارير KPIs وRBAC تفصيلي

---

## ملفات مرتبطة

| الملف | الوصف |
|-------|-------|
| `Watfil_API_Complete.postman_collection.json` | مجموعة Postman كاملة للاختبار |
| `API_ENDPOINTS.md` | توثيق تفصيلي للفرونت (قد لا يشمل Finance) |
| `BACKEND_GAP_REPORT.md` | تقرير الفجوات والخطة المستقبلية |

---

*آخر تحديث: يعكس حالة المشروع بعد Finance Core (الخطة 1).*
ممتاز، خليني أشرحها لك كأنك Laravel Backend Developer مسؤول عن تنفيذ النظام من الصفر، ونفهم **لماذا تم بناء Finance Core بهذه الطريقة** وليس مجرد معرفة الجداول والـ APIs.

---

# لماذا أصلاً نحتاج Finance Core؟

في أي نظام مالي يوجد فرق ضخم بين:

## الطريقة البدائية

```php
$company->wallet_balance += 500;
$company->save();
```

وبين

## الطريقة الاحترافية

```php
WalletPostingService::credit(
    company: $company,
    amount: 500,
    category: 'deposit'
);
```

الفرق أن الطريقة الأولى:

* تغير الرصيد فقط
* لا تعرف ماذا حدث بعد شهر
* لا تعرف من قام بالتعديل
* لا تعرف سبب التعديل
* لا يمكن عمل Audit

أما الثانية:

* تغير الرصيد
* تحفظ الحركة
* تحفظ السبب
* تحفظ المنفذ
* تمنع التكرار
* يمكن مراجعتها محاسبياً

ولهذا تم إنشاء Finance Core.

---

# أولاً: Ledger System

## ما هو Ledger ؟

الـ Ledger هو:

> دفتر الأستاذ المالي

أي سجل يحتوي كل حركة مالية تمت في النظام.

مثل كشف حساب البنك.

---

## مثال

لنفترض شركة رصيدها:

```text
1000 جنيه
```

ثم حدث الآتي:

```text
+500 شحن محفظة
-200 عمولة
-100 طلب سحب
```

بدلاً من رؤية:

```text
1200 جنيه
```

فقط،

سنرى:

| العملية | قبل  | بعد  |
| ------- | ---- | ---- |
| شحن     | 1000 | 1500 |
| عمولة   | 1500 | 1300 |
| سحب     | 1300 | 1200 |

وهذا هو الـ Ledger.

---

# جدول wallet_transactions

هذا أهم جدول في النظام كله.

مثال Record:

```sql
id = 15

company_id = 3

direction = debit

category = commission

amount = 200

balance_before = 1500

balance_after = 1300

performed_by = 1

source = order_completed

idempotency_key = abc-123
```

---

## direction

تحدد نوع الحركة.

### Credit

زيادة رصيد

```text
+500
```

### Debit

خصم رصيد

```text
-500
```

---

## category

سبب الحركة.

مثلاً:

```text
manual_adjustment
commission
withdrawal_hold
withdrawal_release
withdrawal_paid
referral_reward
```

---

## balance_before

الرصيد قبل العملية.

```text
1500
```

---

## balance_after

الرصيد بعد العملية.

```text
1300
```

---

## performed_by

من قام بالحركة.

مثال:

```text
Admin #1
```

أو

```text
System
```

---

## source

مصدر الحركة.

مثلاً:

```text
order
withdrawal
referral
admin
```

---

# لماذا نحتاج balance_before و balance_after؟

بدلاً من حساب التاريخ كاملاً.

مثال:

```text
الحركة رقم 300
```

يمكنك معرفة فوراً:

```text
كان الرصيد 2000
أصبح 1800
```

دون إعادة حساب 299 حركة.

---

# WalletPostingService

هذا أهم Service في الخطة كلها.

---

## قبل Finance Core

الكود كان يفعل:

```php
$company->increment(
    'wallet_balance',
    500
);
```

في أي مكان.

---

## المشكلة

كل Developer يكتب بطريقته.

```php
Controller A
```

يزيد الرصيد.

```php
Controller B
```

يخصم الرصيد.

```php
Controller C
```

ينسى تسجيل الحركة.

فوضى.

---

## بعد Finance Core

أي حركة تمر هنا:

```php
WalletPostingService
```

فقط.

---

## الفكرة

```php
WalletPostingService::post(...)
```

↓

```php
DB Transaction
```

↓

```php
Lock Company Row
```

↓

```php
Update Balance
```

↓

```php
Create Ledger Record
```

↓

```php
Commit
```

---

# لماذا lockForUpdate مهم؟

تخيل:

الرصيد

```text
1000
```

وصل طلبان معاً.

الطلب الأول:

```text
خصم 300
```

الطلب الثاني:

```text
خصم 400
```

بدون Lock:

```text
الاثنان قرأوا 1000
```

الأول:

```text
1000 - 300 = 700
```

الثاني:

```text
1000 - 400 = 600
```

الناتج النهائي:

```text
600
```

خطأ.

المفروض:

```text
300
```

---

## مع lockForUpdate

الأول ينتهي أولاً.

ثم الثاني يقرأ:

```text
700
```

ويخصم:

```text
400
```

فيصبح:

```text
300
```

صحيح.

---

# Idempotency

من أهم مفاهيم الأنظمة المالية.

---

## المشكلة

شركة تضغط زر السحب.

الإنترنت بطيء.

التطبيق يرسل الطلب مرتين.

---

بدون حماية:

```text
سحب 500
سحب 500
```

الرصيد نقص 1000.

كارثة.

---

## الحل

إرسال:

```text
idempotency_key
```

مثلاً:

```text
withdraw-123
```

---

أول مرة:

```text
تنفيذ
```

ثاني مرة بنفس المفتاح:

```text
ارجع النتيجة القديمة
ولا تنفذ ثانية
```

---

# Commission Engine

هذا محرك العمولات.

---

## قبل الخطة

لا يوجد عمولات.

---

## بعد الخطة

عند حدوث Event:

```text
order_completed
```

يتم استدعاء:

```php
CommissionService::apply()
```

---

# Commission Rules

جدول:

```text
commission_rules
```

مثال:

| trigger         | type       | value |
| --------------- | ---------- | ----- |
| order_completed | percentage | 5     |

معناه:

```text
5%
```

على أي طلب مكتمل.

---

# مثال

طلب:

```text
2000 جنيه
```

عمولة:

```text
5%
```

الحساب:

```text
2000 × 5%
```

2000\times0.05=100

العمولة:

```text
100 جنيه
```

---

# Commission Events

بعد حساب العمولة يسجل:

```text
commission_events
```

حتى نعرف:

* الطلب
* المبلغ
* العمولة
* الشركة

---

# إعفاءات العمولة

الخطة أضافت:

```text
exempt_sources
```

مثال:

```json
[
  "referral",
  "internal"
]
```

---

إذا جاء الطلب من:

```text
referral
```

لا تخصم عمولة.

---

# Withdrawal System

هذا أكثر جزء معقد.

---

# لماذا لا نخصم مباشرة؟

لأن السحب يمر بمراجعة.

---

## Step 1

الشركة تطلب:

```http
POST /wallet/withdrawals
```

```json
{
  "amount": 200
}
```

---

## النظام يفعل

إنشاء:

```text
withdrawal_request
```

بحالة:

```text
pending
```

---

ويخصم مؤقتاً:

```text
withdrawal_hold
```

---

الرصيد:

```text
500
```

↓

```text
300
```

---

# لماذا نسميه Hold؟

لأن المال لم يخرج فعلياً.

تم حجزه فقط.

---

# Step 2

Admin يراجع.

---

## Approve

```text
approved
```

---

الرصيد لا يتغير.

لأنه محجوز بالفعل.

---

# Step 3

Admin يحول المبلغ للبنك.

ثم:

```text
paid
```

---

الآن السحب انتهى.

---

# ماذا لو رفض؟

---

الحالة:

```text
rejected
```

---

يتم تنفيذ:

```text
withdrawal_release
```

---

الرصيد:

```text
300
```

↓

```text
500
```

---

# Withdrawal Audits

كل خطوة تسجل.

مثلاً:

```text
Admin #1 approved
```

ثم:

```text
Admin #2 paid
```

---

يمكنك معرفة:

* من وافق
* من رفض
* من دفع
* متى حدث ذلك

---

# APIs الجديدة

## للشركة

### كشف الحساب

```http
GET /company/wallet/transactions
```

ترجع:

```json
[
 {
   "type":"credit",
   "amount":500
 },
 {
   "type":"debit",
   "amount":100
 }
]
```

---

### طلب سحب

```http
POST /company/wallet/withdrawals
```

---

# للإدارة

### طلبات السحب

```http
GET /finance/withdrawal-requests
```

---

### اعتماد

```http
PATCH /withdrawals/{id}/approve
```

---

### رفض

```http
PATCH /withdrawals/{id}/reject
```

---

### دفع

```http
PATCH /withdrawals/{id}/pay
```

---

# لماذا تعتبر هذه الخطة مهمة جداً؟

لأن أي نظام قادم سيبني عليها:

## نظام الطلبات

عند اكتمال الطلب:

```php
CommissionService::apply()
```

---

## نظام الإحالات

عند مكافأة عميل:

```php
WalletPostingService::credit()
```

---

## نظام الإعلانات

عند شراء إعلان:

```php
WalletPostingService::debit()
```

---

## نظام الاستحواذ على العملاء

عند دفع تعويض:

```php
WalletPostingService::transfer()
```

---

# الخلاصة التقنية

الخطة 1 لم تضف مجرد "محفظة".

هي حولت النظام من:

```text
Wallet Balance Only
```

إلى:

```text
Financial Accounting Layer
```

تحتوي على:

* Ledger كامل للحركات
* Wallet Posting Engine موحد
* Commission Engine
* Withdrawal Workflow
* Audit Logs
* Idempotency Protection
* Row Locking
* Financial APIs للشركات والإدارة

وهذا هو الأساس الذي سيعتمد عليه لاحقاً نظام الطلبات، الإعلانات، الإحالات، الاستحواذ على العملاء، وأي حركة مالية داخل المنصة.
