import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const collectionPath = path.join(__dirname, '..', 'Watfil_API_Complete.postman_collection.json');

if (!fs.existsSync(collectionPath)) {
  console.error('Collection not found:', collectionPath);
  process.exit(1);
}

const col = JSON.parse(fs.readFileSync(collectionPath, 'utf8'));

const JSON_HEADERS = [
  { key: 'Accept', value: 'application/json' },
];
const JSON_AUTH = (tokenVar) => [
  ...JSON_HEADERS,
  { key: 'Authorization', value: `Bearer {{${tokenVar}}}` },
];
const JSON_BODY_HEADERS = (tokenVar) => [
  ...JSON_AUTH(tokenVar),
  { key: 'Content-Type', value: 'application/json' },
];

function url(raw, pathSegments, query = []) {
  return {
    raw,
    host: ['{{base_url}}'],
    path: pathSegments,
    ...(query.length ? { query } : {}),
  };
}

function req(method, pathSegments, opts = {}) {
  const segments = Array.isArray(pathSegments) ? pathSegments : pathSegments.split('/').filter(Boolean);
  const raw = opts.raw ?? `{{base_url}}/${segments.join('/')}${opts.querySuffix ?? ''}`;
  return {
    name: opts.name,
    request: {
      method,
      header: opts.header ?? JSON_AUTH(opts.token ?? 'super_admin_token'),
      url: url(raw, segments, opts.query ?? []),
      description: opts.description ?? '',
      ...(opts.body ? { body: { mode: 'raw', raw: opts.body, options: { raw: { language: 'json' } } } } : {}),
    },
    ...(opts.event ? { event: opts.event } : {}),
  };
}

function endpointKey(item) {
  if (!item.request) return null;
  const method = item.request.method;
  let segments = item.request.url?.path;
  if (!segments && item.request.url?.raw) {
    segments = item.request.url.raw
      .replace(/\{\{base_url\}\}\/?/, '')
      .split('?')[0]
      .split('/')
      .filter(Boolean);
  }
  if (!segments) return null;
  const normalized = segments.map((s) => (s.startsWith('{{') ? ':var' : s)).join('/');
  return `${method} ${normalized}`;
}

function dedupeFolderItems(items, removeNames = []) {
  const removeSet = new Set(removeNames);
  const seen = new Set();
  return items.filter((item) => {
    if (removeSet.has(item.name)) return false;
    const key = endpointKey(item);
    if (!key) return true;
    if (seen.has(key)) return false;
    seen.add(key);
    return true;
  });
}

function findFolder(items, name) {
  return items.find((i) => i.name === name || i.name.endsWith(` — ${name}`));
}

function findSubfolder(parent, name) {
  return parent?.item?.find((i) => i.name === name);
}

const SETUP_GUIDE = {
  name: '00 — Setup Guide',
  description: `# دليل إعداد بيئة الاختبار

شغّل الطلبات **بالترتيب** من المجلدات التالية (كل endpoint موجود مرة واحدة فقط):

1. **01 — Super Admin → Auth → Login**
2. **01 — Super Admin → Governorates → List**
3. **01 — Super Admin → Companies → Create** (يستخدم \`company_tax_number\` / \`company_password\`)
4. **01 — Super Admin → Company Wallet → Adjust Wallet** (type: credit — شحن 1000)
5. **01 — Super Admin → Suppliers → Create**
6. **01 — Super Admin → Supplier Products → Create**
7. **02 — Company → Auth → Login**
8. **02 — Company → Catalog → Add (bulk)**
9. **02 — Company → Products → Create**
10. **03 — Customer → Auth → Register**
11. **02 — Company → Orders → Create Order**

> بعد \`migrate --seed\` يمكنك تخطي خطوات 1–6 واستخدام حسابات الـ seeder مباشرة.`,
  item: [],
};

const MODULE_RENAMES = {
  'Super Admin': '01 — Super Admin',
  '01 — Super Admin': '01 — Super Admin',
  'Company': '02 — Company',
  '02 — Company': '02 — Company',
  'Customer': '03 — Customer',
  '03 — Customer': '03 — Customer',
};

// Remove duplicate setup / legacy flow folders — endpoints live only in modules
col.item = col.item.filter(
  (i) =>
    ![
      '00 — Setup Flow',
      '00 — Setup Guide',
      '01 — Withdrawal Flow',
      '02 — Customer Flow',
      '03 — Order Flow',
    ].includes(i.name)
);

if (!col.variable.some((v) => v.key === 'installment_contract_id')) {
  col.variable.push({ key: 'installment_contract_id', value: '1', type: 'string' });
}
if (!col.variable.some((v) => v.key === 'installment_schedule_id')) {
  col.variable.push({ key: 'installment_schedule_id', value: '1', type: 'string' });
}

// Rename top-level modules
for (const folder of col.item) {
  const renamed = MODULE_RENAMES[folder.name];
  if (renamed) folder.name = renamed;
}

// Insert setup guide at the top
col.item.unshift(SETUP_GUIDE);

const superAdmin = findFolder(col.item, 'Super Admin');
const company = findFolder(col.item, 'Company');
const customer = findFolder(col.item, 'Customer');

const companyWallet = findSubfolder(superAdmin, 'Company Wallet');
if (companyWallet) {
  companyWallet.item = dedupeFolderItems(companyWallet.item, ['Adjust Debit']);
  const adjust = companyWallet.item.find((i) => i.name === 'Adjust Credit' || i.name === 'Adjust Wallet');
  if (adjust && adjust.name === 'Adjust Credit') adjust.name = 'Adjust Wallet';
  if (adjust) {
    adjust.request.description = 'type: credit أو debit — أمثلة: credit لإضافة، debit للخصم';
  }
}

const companies = findSubfolder(superAdmin, 'Companies');
if (companies) {
  const create = companies.item.find((i) => i.name === 'Create');
  if (create?.request?.body) {
    create.request.body.raw =
      '{\n  "name": "شركة اختبار واتفيل",\n  "tax_number": "{{company_tax_number}}",\n  "password": "{{company_password}}",\n  "governorate_id": {{governorate_id}},\n  "is_active": true\n}';
  }
}

const products = findSubfolder(company, 'Products');
if (products) {
  products.item = dedupeFolderItems(products.item, ['Create (cash only)', 'Remove Installments']);
  const create = products.item.find((i) => i.name === 'Create (with installments)' || i.name === 'Create');
  if (create && create.name === 'Create (with installments)') create.name = 'Create';
}

const customers = findSubfolder(company, 'Customers');
if (customers) {
  customers.item = dedupeFolderItems(customers.item, ['List (search & filter)']);
  const list = customers.item.find((i) => i.name === 'List Linked Customers' || i.name === 'List');
  if (list) {
    list.name = 'List';
    list.request.url.query = [
      { key: 'per_page', value: '15' },
      { key: 'status', value: 'active', disabled: true },
      { key: 'search', value: 'أحمد', disabled: true },
    ];
    list.request.url.raw = '{{base_url}}/company/customers?per_page=15';
    list.request.description = 'قائمة العملاء المرتبطين — فلاتر اختيارية: status, search, per_page';
  }
}

const companyOrders = findSubfolder(company, 'Orders');
if (companyOrders) {
  companyOrders.item = dedupeFolderItems(companyOrders.item, [
    'Update Status → completed',
    'Cancel Order',
  ]);
  const updateStatus = companyOrders.item.find(
    (i) => i.name === 'Update Status → processing' || i.name === 'Update Status'
  );
  if (updateStatus) {
    updateStatus.name = 'Update Status';
    updateStatus.request.description =
      'status: pending | processing | completed | cancelled — أضف cancellation_reason عند الإلغاء';
    updateStatus.request.body.raw =
      '{\n  "status": "processing",\n  "note": "جاري التجهيز"\n}';
  }
}

if (superAdmin && !findSubfolder(superAdmin, 'Installment Contracts')) {
  superAdmin.item.push({
    name: 'Installment Contracts',
    description: 'مراقبة عقود التقسيط (قراءة فقط)',
    item: [
      req('GET', ['super-admin', 'installment-contracts', 'overdue-summary'], {
        name: 'Overdue Summary',
        token: 'super_admin_token',
        description: 'ملخص الأقساط المتأخرة',
      }),
      req('GET', ['super-admin', 'installment-contracts'], {
        name: 'List',
        token: 'super_admin_token',
        query: [
          { key: 'per_page', value: '15' },
          { key: 'status', value: 'active', disabled: true },
          { key: 'company_id', value: '{{company_id}}', disabled: true },
          { key: 'customer_id', value: '{{customer_id}}', disabled: true },
        ],
        raw: '{{base_url}}/super-admin/installment-contracts?per_page=15',
        description: 'قائمة عقود التقسيط مع فلاتر اختيارية',
      }),
      req('GET', ['super-admin', 'installment-contracts', '{{installment_contract_id}}'], {
        name: 'Show',
        token: 'super_admin_token',
        description: 'تفاصيل عقد تقسيط',
      }),
    ],
  });
}

if (company && !findSubfolder(company, 'Installment Contracts')) {
  company.item.push({
    name: 'Installment Contracts',
    description: 'عقود التقسيط وتحصيل الأقساط',
    item: [
      req('GET', ['company', 'installment-contracts'], {
        name: 'List',
        token: 'company_token',
        query: [
          { key: 'per_page', value: '15' },
          { key: 'status', value: 'active', disabled: true },
          { key: 'customer_id', value: '{{customer_id}}', disabled: true },
        ],
        raw: '{{base_url}}/company/installment-contracts?per_page=15',
        description: 'قائمة عقود التقسيط للشركة',
        event: [
          {
            listen: 'test',
            script: {
              type: 'text/javascript',
              exec: [
                'var res = pm.response.json();',
                'if (res.data && res.data.length) {',
                '  pm.collectionVariables.set("installment_contract_id", String(res.data[0].id));',
                '  if (res.data[0].schedule && res.data[0].schedule.length) {',
                '    pm.collectionVariables.set("installment_schedule_id", String(res.data[0].schedule[0].id));',
                '  }',
                '}',
              ],
            },
          },
        ],
      }),
      req('GET', ['company', 'installment-contracts', '{{installment_contract_id}}'], {
        name: 'Show',
        token: 'company_token',
        description: 'تفاصيل عقد مع الجدول والدفعات',
      }),
      req('POST', ['company', 'installment-contracts', '{{installment_contract_id}}', 'payments'], {
        name: 'Record Payment',
        token: 'company_token',
        header: JSON_BODY_HEADERS('company_token'),
        description: 'تسجيل دفعة قسط — payment_method: cash|transfer|card|other',
        body: '{\n  "installment_schedule_id": {{installment_schedule_id}},\n  "amount": 800,\n  "payment_method": "cash",\n  "notes": "دفعة شهرية",\n  "idempotency_key": "inst-pay-001"\n}',
      }),
    ],
  });
}

if (customer && !findSubfolder(customer, 'Installment Contracts')) {
  customer.item.push({
    name: 'Installment Contracts',
    description: 'عقود التقسيط الخاصة بالعميل',
    item: [
      req('GET', ['customer', 'installment-contracts'], {
        name: 'List',
        token: 'customer_token',
        query: [
          { key: 'per_page', value: '15' },
          { key: 'status', value: 'active', disabled: true },
        ],
        raw: '{{base_url}}/customer/installment-contracts?per_page=15',
        description: 'قائمة عقود التقسيط للعميل',
      }),
      req('GET', ['customer', 'installment-contracts', '{{installment_contract_id}}'], {
        name: 'Show',
        token: 'customer_token',
        description: 'تفاصيل عقد التقسيط',
      }),
    ],
  });
}

col.info.description = `# Watfil Backend — مجموعة Postman الكاملة

## الإعداد
1. \`php artisan migrate --seed\`
2. استورد الملف في Postman
3. اتبع **00 — Setup Guide** لتشغيل الطلبات بالترتيب من الـ modules

## حسابات افتراضية
- Super Admin: \`admin@watafl.com\` / \`Admin@1234\`
- Company: أنشئها من Setup Guide أو استخدم \`company_tax_number\` / \`company_password\`
- Customer: سجّل من Setup Guide أو استخدم \`customer_phone\` / \`customer_password\`

## هيكل المجموعة
| Module | الوصف |
|--------|-------|
| 01 — Super Admin | إدارة النظام، الشركات، الموردين، المالية |
| 02 — Company | منتجات، كatalog، طلبات، محفظة، تقسيط |
| 03 — Customer | تسجيل، طلبات، عقود تقسيط |

## المتغيرات
| Variable | الوصف |
|----------|-------|
| base_url | http://localhost:8000/api |
| super_admin_token | token الأدمن |
| company_token | token الشركة |
| company_id | ID الشركة |
| customer_token | token العميل |
| customer_id | ID العميل |
| customer_phone | رقم موبايل العميل |
| customer_password | كلمة مرور العميل |
| product_id | ID منتج الشركة |
| order_id | ID آخر طلب |
| installment_contract_id | ID عقد التقسيط |
| installment_schedule_id | ID قسط في الجدول |
| withdrawal_request_id | ID طلب السحب |`;

fs.writeFileSync(collectionPath, JSON.stringify(col, null, 2) + '\n', 'utf8');

function walk(items, folder = '') {
  const out = [];
  for (const it of items) {
    if (it.item) out.push(...walk(it.item, folder ? `${folder} / ${it.name}` : it.name));
    else if (it.request) out.push({ folder, name: it.name, key: endpointKey(it) });
  }
  return out;
}

const endpoints = walk(col.item);
const keys = endpoints.map((e) => e.key);
const dupes = keys.filter((k, i) => keys.indexOf(k) !== i);
const uniqueDupes = [...new Set(dupes)];

const expected = [
  'POST super-admin/login', 'POST super-admin/logout', 'GET super-admin/me', 'GET super-admin/governorates',
  'GET super-admin/companies', 'POST super-admin/companies', 'GET super-admin/companies/:var', 'POST super-admin/companies/:var', 'DELETE super-admin/companies/:var',
  'PATCH super-admin/companies/:var/toggle-status', 'GET super-admin/companies/:var/wallet', 'PATCH super-admin/companies/:var/wallet', 'POST super-admin/companies/:var/wallet/adjust', 'GET super-admin/companies/:var/wallet/transactions',
  'GET super-admin/finance/commissions/summary', 'GET super-admin/finance/withdrawal-requests', 'PATCH super-admin/finance/withdrawal-requests/:var/approve', 'PATCH super-admin/finance/withdrawal-requests/:var/reject', 'PATCH super-admin/finance/withdrawal-requests/:var/pay',
  'GET super-admin/suppliers', 'POST super-admin/suppliers', 'GET super-admin/suppliers/:var', 'POST super-admin/suppliers/:var', 'DELETE super-admin/suppliers/:var',
  'GET super-admin/supplier-products', 'POST super-admin/supplier-products', 'GET super-admin/supplier-products/:var', 'POST super-admin/supplier-products/:var', 'DELETE super-admin/supplier-products/:var', 'PATCH super-admin/supplier-products/:var/toggle-status',
  'GET super-admin/orders', 'GET super-admin/orders/:var',
  'GET super-admin/installment-contracts/overdue-summary', 'GET super-admin/installment-contracts', 'GET super-admin/installment-contracts/:var',
  'POST company/login', 'POST company/logout', 'GET company/me',
  'GET company/products', 'POST company/products', 'GET company/products/:var', 'POST company/products/:var', 'DELETE company/products/:var',
  'GET company/catalog/available', 'GET company/catalog/mine', 'POST company/catalog/add', 'POST company/catalog/remove', 'POST company/catalog/:var', 'DELETE company/catalog/:var',
  'GET company/wallet/transactions', 'POST company/wallet/withdrawals',
  'GET company/customers',
  'GET company/orders', 'POST company/orders', 'GET company/orders/:var', 'PATCH company/orders/:var/status',
  'GET company/installment-contracts', 'GET company/installment-contracts/:var', 'POST company/installment-contracts/:var/payments',
  'POST customer/register', 'POST customer/login', 'POST customer/logout', 'GET customer/me', 'PATCH customer/profile',
  'GET customer/orders', 'POST customer/orders', 'GET customer/orders/:var',
  'GET customer/installment-contracts', 'GET customer/installment-contracts/:var',
];

const present = new Set(keys);
const missing = expected.filter((e) => !present.has(e));

console.log('Updated:', collectionPath);
console.log(`Total requests: ${endpoints.length}`);
console.log(`Unique endpoints: ${present.size}`);
console.log('Missing:', missing.length ? missing.join(', ') : 'none');

if (uniqueDupes.length) {
  console.error('Duplicate endpoints found:');
  uniqueDupes.forEach((k) => {
    const matches = endpoints.filter((e) => e.key === k);
    console.error(`  ${k}: ${matches.map((m) => m.folder + ' > ' + m.name).join(' | ')}`);
  });
  process.exit(1);
}

console.log('Duplicates: none');
