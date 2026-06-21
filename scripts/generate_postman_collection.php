<?php

/**
 * Generates Watfil_API_Complete.postman_collection.json
 * Run: php scripts/generate_postman_collection.php
 */

$accept = ['key' => 'Accept', 'value' => 'application/json'];
$json   = ['key' => 'Content-Type', 'value' => 'application/json'];

function hdr(array ...$headers): array
{
    return array_values(array_filter($headers));
}

function auth(string $tokenVar = 'customer_token'): array
{
    return ['key' => 'Authorization', 'value' => "Bearer {{$tokenVar}}"];
}

function url(string $path, array $query = []): array
{
    $segments = explode('/', trim($path, '/'));
    $raw      = '{{base_url}}/' . implode('/', $segments);
    $q        = [];

    foreach ($query as $k => $v) {
        $entry = is_array($v) ? $v : ['key' => $k, 'value' => (string) $v, 'disabled' => false];
        $q[]   = $entry;
        if (empty($entry['disabled'])) {
            $raw .= (str_contains($raw, '?') ? '&' : '?') . $entry['key'] . '=' . $entry['value'];
        }
    }

    return [
        'raw'   => $raw,
        'host'  => ['{{base_url}}'],
        'path'  => $segments,
        'query' => $q ?: null,
    ];
}

function req(string $method, string $path, string $description, array $headers = [], ?array $body = null, array $query = [], ?array $event = null): array
{
    $request = [
        'method'      => $method,
        'header'      => $headers,
        'url'         => url($path, $query),
        'description' => $description,
    ];

    if ($body !== null) {
        $request['body'] = $body;
    }

    $item = ['name' => '', 'request' => $request];

    if ($event) {
        $item['event'] = $event;
    }

    return $item;
}

function rawBody(string $json): array
{
    return [
        'mode'    => 'raw',
        'raw'     => $json,
        'options' => ['raw' => ['language' => 'json']],
    ];
}

function saveToken(string $var, string $path = 'token'): array
{
    return [[
        'listen' => 'test',
        'script' => [
            'type' => 'text/javascript',
            'exec' => [
                "var res = pm.response.json();",
                "if (res.{$path}) pm.collectionVariables.set(\"{$var}\", res.{$path});",
            ],
        ],
    ]];
}

function saveIdFromData(string $var): array
{
    return [[
        'listen' => 'test',
        'script' => [
            'type' => 'text/javascript',
            'exec' => [
                "if (pm.response.code >= 200 && pm.response.code < 300) {",
                "  var res = pm.response.json();",
                "  if (res.data && res.data.id) pm.collectionVariables.set(\"{$var}\", String(res.data.id));",
                "  if (res.customer && res.customer.id) pm.collectionVariables.set(\"{$var}\", String(res.customer.id));",
                "}",
            ],
        ],
    ]];
}

function folder(string $name, string $description, array $items): array
{
    return ['name' => $name, 'description' => $description, 'item' => $items];
}

function named(string $name, array $item): array
{
    $item['name'] = $name;

    return $item;
}

function endpointKey(string $method, string $path): string
{
    return strtoupper($method) . ' ' . $path;
}

/** @return array<string, array{method:string,path:string,name:string,description:string,headers:array,body:?array,query:array,event:?array}> */
function allEndpoints(): array
{
    global $accept, $json;

    $a  = fn (string $t = 'customer_token') => auth($t);
    $h  = fn (...$x) => hdr(...$x);
    $rb = fn (string $s) => rawBody($s);

    $list = [
        // ── Public (8) ──
        endpointKey('GET', 'public/governorates') => [
            'method' => 'GET', 'path' => 'public/governorates', 'name' => 'GET Governorates',
            'description' => "## GET /public/governorates\n\n**Auth:** لا\n\n**Response 200:**\n```json\n{ \"data\": [{ \"id\": 1, \"name_ar\": \"...\", \"name_en\": \"...\" }] }\n```",
            'headers' => $h($accept), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('GET', 'public/product-types') => [
            'method' => 'GET', 'path' => 'public/product-types', 'name' => 'GET Product Types',
            'description' => "## GET /public/product-types\n\n**Auth:** لا\n\n**Response:** device | accessories | stages للفلترة",
            'headers' => $h($accept), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('GET', 'public/categories') => [
            'method' => 'GET', 'path' => 'public/categories', 'name' => 'GET Categories',
            'description' => "## GET /public/categories\n\n**Auth:** لا\n\n**Query:** product_type_id, parent_category_id (0=جذر), number_of_stages",
            'headers' => $h($accept), 'body' => null, 'query' => ['product_type_id' => '{{product_type_id}}', 'parent_category_id' => '0'], 'event' => null,
        ],
        endpointKey('GET', 'public/companies') => [
            'method' => 'GET', 'path' => 'public/companies', 'name' => 'GET Companies by Governorate',
            'description' => "## GET /public/companies\n\n**Auth:** لا (Bearer اختياري لـ is_liked)\n\n**Query (required):** `governorate_id`\n\n**Response:** شركات نشطة + likes_count + ratings_avg",
            'headers' => $h($accept), 'body' => null, 'query' => ['governorate_id' => '{{governorate_id}}'], 'event' => null,
        ],
        endpointKey('GET', 'public/companies/{{company_id}}') => [
            'method' => 'GET', 'path' => 'public/companies/{{company_id}}', 'name' => 'GET Company Details',
            'description' => "## GET /public/companies/{company}\n\n**Auth:** لا\n\n**Response:** تفاصيل شركة + governorate + إحصائيات",
            'headers' => $h($accept), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('GET', 'public/companies/{{company_id}}/products') => [
            'method' => 'GET', 'path' => 'public/companies/{{company_id}}/products', 'name' => 'GET Store Products',
            'description' => "## GET /public/companies/{company}/products\n\n**Auth:** لا\n\n**Query:** page, category_id, product_type_id, number_of_stages\n\n**Response:** منتجات + category + installment_plans",
            'headers' => $h($accept), 'body' => null, 'query' => ['page' => '1', 'category_id' => ['key' => 'category_id', 'value' => '{{category_id}}', 'disabled' => true], 'product_type_id' => ['key' => 'product_type_id', 'value' => '{{product_type_id}}', 'disabled' => true]], 'event' => null,
        ],
        endpointKey('GET', 'public/companies/{{company_id}}/products/{{product_id}}/installment-plans') => [
            'method' => 'GET', 'path' => 'public/companies/{{company_id}}/products/{{product_id}}/installment-plans', 'name' => 'GET Product Installment Plans',
            'description' => "## GET /public/companies/{company}/products/{companyProduct}/installment-plans\n\n**Auth:** لا\n\n**Response:** cash_price + plans[] مع remaining_amount و total_amount\n\nاستخدم قبل زر \"قسط من هنا\"",
            'headers' => $h($accept), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('GET', 'public/maintenance/lookups') => [
            'method' => 'GET', 'path' => 'public/maintenance/lookups', 'name' => 'GET Maintenance Lookups',
            'description' => "## GET /public/maintenance/lookups\n\n**Auth:** لا\n\n**Response:** purification_systems, stages_counts, primary_problem_types, malfunction_types, max_stages\n\nاستخدم القيم في نموذج طلب الصيانة",
            'headers' => $h($accept), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Super Admin Auth (3) ──
        endpointKey('POST', 'super-admin/login') => [
            'method' => 'POST', 'path' => 'super-admin/login', 'name' => 'POST Login',
            'description' => "## POST /super-admin/login\n\n**Body:** email, password\n\n**Response 200:** `{ token, admin }`",
            'headers' => $h($accept, $json), 'body' => $rb("{\n  \"email\": \"admin@watafl.com\",\n  \"password\": \"Admin@1234\"\n}"),
            'query' => [], 'event' => saveToken('super_admin_token'),
        ],
        endpointKey('POST', 'super-admin/logout') => [
            'method' => 'POST', 'path' => 'super-admin/logout', 'name' => 'POST Logout',
            'description' => "## POST /super-admin/logout\n\n**Auth:** Bearer {{super_admin_token}}",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('GET', 'super-admin/me') => [
            'method' => 'GET', 'path' => 'super-admin/me', 'name' => 'GET Me',
            'description' => "## GET /super-admin/me\n\n**Auth:** Bearer {{super_admin_token}}",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Super Admin Governorates (1) ──
        endpointKey('GET', 'super-admin/governorates') => [
            'method' => 'GET', 'path' => 'super-admin/governorates', 'name' => 'GET Governorates',
            'description' => "## GET /super-admin/governorates\n\n**Auth:** Bearer {{super_admin_token}}\n\nقراءة فقط — تُملأ من Seeder",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Super Admin Product Types (5) ──
        endpointKey('GET', 'super-admin/product-types') => [
            'method' => 'GET', 'path' => 'super-admin/product-types', 'name' => 'GET List Product Types',
            'description' => "## GET /super-admin/product-types\n\n**Auth:** Bearer {{super_admin_token}}",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'super-admin/product-types') => [
            'method' => 'POST', 'path' => 'super-admin/product-types', 'name' => 'POST Create Product Type',
            'description' => "## POST /super-admin/product-types\n\n**Body:** name (unique slug), name_ar",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"name\": \"device\",\n  \"name_ar\": \"فلتر مياه\"\n}"),
            'query' => [], 'event' => saveIdFromData('product_type_id'),
        ],
        endpointKey('GET', 'super-admin/product-types/{{product_type_id}}') => [
            'method' => 'GET', 'path' => 'super-admin/product-types/{{product_type_id}}', 'name' => 'GET Show Product Type',
            'description' => "## GET /super-admin/product-types/{productType}",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'super-admin/product-types/{{product_type_id}}') => [
            'method' => 'POST', 'path' => 'super-admin/product-types/{{product_type_id}}', 'name' => 'POST Update Product Type',
            'description' => "## POST /super-admin/product-types/{productType}",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"name_ar\": \"فلتر مياه محدث\"\n}"),
            'query' => [], 'event' => null,
        ],
        endpointKey('DELETE', 'super-admin/product-types/{{product_type_id}}') => [
            'method' => 'DELETE', 'path' => 'super-admin/product-types/{{product_type_id}}', 'name' => 'DELETE Product Type',
            'description' => "## DELETE /super-admin/product-types/{productType}",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Super Admin Categories (5) ──
        endpointKey('GET', 'super-admin/categories') => [
            'method' => 'GET', 'path' => 'super-admin/categories', 'name' => 'GET List Categories',
            'description' => "## GET /super-admin/categories\n\n**Query:** product_type_id, parent_category_id (0=جذر), search, per_page",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => ['per_page' => '50', 'product_type_id' => '{{product_type_id}}'], 'event' => null,
        ],
        endpointKey('POST', 'super-admin/categories') => [
            'method' => 'POST', 'path' => 'super-admin/categories', 'name' => 'POST Create Category',
            'description' => "## POST /super-admin/categories\n\n**Body:** name, parent_category_id (0=جذر), product_type_id, number_of_stages",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"name\": \"RO devices\",\n  \"parent_category_id\": 0,\n  \"product_type_id\": {{product_type_id}},\n  \"number_of_stages\": 0\n}"),
            'query' => [], 'event' => saveIdFromData('category_id'),
        ],
        endpointKey('GET', 'super-admin/categories/{{category_id}}') => [
            'method' => 'GET', 'path' => 'super-admin/categories/{{category_id}}', 'name' => 'GET Show Category',
            'description' => "## GET /super-admin/categories/{category}",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'super-admin/categories/{{category_id}}') => [
            'method' => 'POST', 'path' => 'super-admin/categories/{{category_id}}', 'name' => 'POST Update Category',
            'description' => "## POST /super-admin/categories/{category}",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"name\": \"صنف محدث\"\n}"),
            'query' => [], 'event' => null,
        ],
        endpointKey('DELETE', 'super-admin/categories/{{category_id}}') => [
            'method' => 'DELETE', 'path' => 'super-admin/categories/{{category_id}}', 'name' => 'DELETE Category',
            'description' => "## DELETE /super-admin/categories/{category}",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Super Admin Companies (6) ──
        endpointKey('GET', 'super-admin/companies') => [
            'method' => 'GET', 'path' => 'super-admin/companies', 'name' => 'GET List Companies',
            'description' => "## GET /super-admin/companies\n\n**Query:** search, governorate_id, is_active, per_page",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => ['per_page' => '15'], 'event' => null,
        ],
        endpointKey('POST', 'super-admin/companies') => [
            'method' => 'POST', 'path' => 'super-admin/companies', 'name' => 'POST Create Company',
            'description' => "## POST /super-admin/companies\n\n**Body:** name, tax_number, password, governorate_id, is_active\n\n**multipart** إذا في logo",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"name\": \"شركة جديدة\",\n  \"tax_number\": \"{{company_tax_number}}\",\n  \"password\": \"{{company_password}}\",\n  \"governorate_id\": {{governorate_id}},\n  \"is_active\": true\n}"),
            'query' => [], 'event' => saveIdFromData('company_id'),
        ],
        endpointKey('GET', 'super-admin/companies/{{company_id}}') => [
            'method' => 'GET', 'path' => 'super-admin/companies/{{company_id}}', 'name' => 'GET Show Company',
            'description' => "## GET /super-admin/companies/{company}",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'super-admin/companies/{{company_id}}') => [
            'method' => 'POST', 'path' => 'super-admin/companies/{{company_id}}', 'name' => 'POST Update Company',
            'description' => "## POST /super-admin/companies/{company}\n\n**ملاحظة:** POST وليس PUT — يدعم multipart",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"name\": \"شركة محدّثة\"\n}"), 'query' => [], 'event' => null,
        ],
        endpointKey('PATCH', 'super-admin/companies/{{company_id}}/toggle-status') => [
            'method' => 'PATCH', 'path' => 'super-admin/companies/{{company_id}}/toggle-status', 'name' => 'PATCH Toggle Status',
            'description' => "## PATCH /super-admin/companies/{company}/toggle-status\n\nتفعيل/إيقاف الشركة",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('DELETE', 'super-admin/companies/{{company_id}}') => [
            'method' => 'DELETE', 'path' => 'super-admin/companies/{{company_id}}', 'name' => 'DELETE Company',
            'description' => "## DELETE /super-admin/companies/{company}",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Super Admin Company Wallet (5) ──
        endpointKey('GET', 'super-admin/companies/{{company_id}}/wallet') => [
            'method' => 'GET', 'path' => 'super-admin/companies/{{company_id}}/wallet', 'name' => 'GET Show Wallet',
            'description' => "## GET /super-admin/companies/{company}/wallet\n\nرصيد + آخر 5 حركات",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('PATCH', 'super-admin/companies/{{company_id}}/wallet') => [
            'method' => 'PATCH', 'path' => 'super-admin/companies/{{company_id}}/wallet', 'name' => 'PATCH Set Balance',
            'description' => "## PATCH /super-admin/companies/{company}/wallet\n\n**Body:** wallet_balance — تعيين مباشر (حذر!)",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"wallet_balance\": 5000\n}"), 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'super-admin/companies/{{company_id}}/wallet/adjust') => [
            'method' => 'POST', 'path' => 'super-admin/companies/{{company_id}}/wallet/adjust', 'name' => 'POST Adjust Wallet',
            'description' => "## POST /super-admin/companies/{company}/wallet/adjust\n\n**Body:** amount, type (credit|debit), reason, idempotency_key",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"amount\": 500,\n  \"type\": \"credit\",\n  \"reason\": \"شحن\",\n  \"idempotency_key\": \"adj-001\"\n}"),
            'query' => [], 'event' => null,
        ],
        endpointKey('GET', 'super-admin/companies/{{company_id}}/wallet/transactions') => [
            'method' => 'GET', 'path' => 'super-admin/companies/{{company_id}}/wallet/transactions', 'name' => 'GET Wallet Transactions',
            'description' => "## GET /super-admin/companies/{company}/wallet/transactions\n\n**Query:** direction, category, from, to, per_page",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => ['per_page' => '15'], 'event' => null,
        ],

        // ── Super Admin Finance (5) ──
        endpointKey('GET', 'super-admin/finance/commissions/summary') => [
            'method' => 'GET', 'path' => 'super-admin/finance/commissions/summary', 'name' => 'GET Commission Summary',
            'description' => "## GET /super-admin/finance/commissions/summary\n\n**Query:** from, to",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null,
            'query' => ['from' => '2026-01-01', 'to' => '2026-12-31'], 'event' => null,
        ],
        endpointKey('GET', 'super-admin/finance/withdrawal-requests') => [
            'method' => 'GET', 'path' => 'super-admin/finance/withdrawal-requests', 'name' => 'GET Withdrawal Requests',
            'description' => "## GET /super-admin/finance/withdrawal-requests\n\n**Query:** status, company_id",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => ['status' => 'pending'], 'event' => null,
        ],
        endpointKey('PATCH', 'super-admin/finance/withdrawal-requests/{{withdrawal_request_id}}/approve') => [
            'method' => 'PATCH', 'path' => 'super-admin/finance/withdrawal-requests/{{withdrawal_request_id}}/approve', 'name' => 'PATCH Approve Withdrawal',
            'description' => "## PATCH .../approve\n\n**Body:** note (optional)",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"note\": \"تمت المراجعة\"\n}"), 'query' => [], 'event' => null,
        ],
        endpointKey('PATCH', 'super-admin/finance/withdrawal-requests/{{withdrawal_request_id}}/reject') => [
            'method' => 'PATCH', 'path' => 'super-admin/finance/withdrawal-requests/{{withdrawal_request_id}}/reject', 'name' => 'PATCH Reject Withdrawal',
            'description' => "## PATCH .../reject\n\n**Body:** reason (required) — يُرجع الرصيد المحجوز",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"reason\": \"بيانات غير مكتملة\"\n}"), 'query' => [], 'event' => null,
        ],
        endpointKey('PATCH', 'super-admin/finance/withdrawal-requests/{{withdrawal_request_id}}/pay') => [
            'method' => 'PATCH', 'path' => 'super-admin/finance/withdrawal-requests/{{withdrawal_request_id}}/pay', 'name' => 'PATCH Pay Withdrawal',
            'description' => "## PATCH .../pay\n\n**Body:** payment_reference (optional)",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"payment_reference\": \"TRX-12345\"\n}"), 'query' => [], 'event' => null,
        ],

        // ── Super Admin Suppliers (5) ──
        endpointKey('GET', 'super-admin/suppliers') => [
            'method' => 'GET', 'path' => 'super-admin/suppliers', 'name' => 'GET List Suppliers',
            'description' => "## GET /super-admin/suppliers",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'super-admin/suppliers') => [
            'method' => 'POST', 'path' => 'super-admin/suppliers', 'name' => 'POST Create Supplier',
            'description' => "## POST /super-admin/suppliers\n\n**Body:** name, phone, email, address",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"name\": \"مورد\",\n  \"phone\": \"01000000000\",\n  \"email\": \"s@test.com\",\n  \"address\": \"القاهرة\"\n}"),
            'query' => [], 'event' => saveIdFromData('supplier_id'),
        ],
        endpointKey('GET', 'super-admin/suppliers/{{supplier_id}}') => [
            'method' => 'GET', 'path' => 'super-admin/suppliers/{{supplier_id}}', 'name' => 'GET Show Supplier',
            'description' => "## GET /super-admin/suppliers/{supplier}",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'super-admin/suppliers/{{supplier_id}}') => [
            'method' => 'POST', 'path' => 'super-admin/suppliers/{{supplier_id}}', 'name' => 'POST Update Supplier',
            'description' => "## POST /super-admin/suppliers/{supplier}",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"name\": \"مورد محدّث\"\n}"), 'query' => [], 'event' => null,
        ],
        endpointKey('DELETE', 'super-admin/suppliers/{{supplier_id}}') => [
            'method' => 'DELETE', 'path' => 'super-admin/suppliers/{{supplier_id}}', 'name' => 'DELETE Supplier',
            'description' => "## DELETE /super-admin/suppliers/{supplier}",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Super Admin Supplier Products (6) ──
        endpointKey('GET', 'super-admin/supplier-products') => [
            'method' => 'GET', 'path' => 'super-admin/supplier-products', 'name' => 'GET List Supplier Products',
            'description' => "## GET /super-admin/supplier-products\n\n**Query:** supplier_id, is_active",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => ['supplier_id' => '{{supplier_id}}'], 'event' => null,
        ],
        endpointKey('POST', 'super-admin/supplier-products') => [
            'method' => 'POST', 'path' => 'super-admin/supplier-products', 'name' => 'POST Create Supplier Product',
            'description' => "## POST /super-admin/supplier-products\n\n**Body:** name, cash_price, supplier_id, installment_plans[]",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"name\": \"فلتر\",\n  \"cash_price\": 5000,\n  \"supplier_id\": {{supplier_id}},\n  \"is_active\": true,\n  \"installment_plans\": [{ \"months\": 6, \"down_payment\": 500, \"installment_amount\": 800 }]\n}"),
            'query' => [], 'event' => saveIdFromData('supplier_product_id'),
        ],
        endpointKey('GET', 'super-admin/supplier-products/{{supplier_product_id}}') => [
            'method' => 'GET', 'path' => 'super-admin/supplier-products/{{supplier_product_id}}', 'name' => 'GET Show Supplier Product',
            'description' => "## GET /super-admin/supplier-products/{supplierProduct}",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'super-admin/supplier-products/{{supplier_product_id}}') => [
            'method' => 'POST', 'path' => 'super-admin/supplier-products/{{supplier_product_id}}', 'name' => 'POST Update Supplier Product',
            'description' => "## POST /super-admin/supplier-products/{supplierProduct}",
            'headers' => $h($accept, $a('super_admin_token'), $json),
            'body' => $rb("{\n  \"name\": \"منتج محدّث\"\n}"), 'query' => [], 'event' => null,
        ],
        endpointKey('PATCH', 'super-admin/supplier-products/{{supplier_product_id}}/toggle-status') => [
            'method' => 'PATCH', 'path' => 'super-admin/supplier-products/{{supplier_product_id}}/toggle-status', 'name' => 'PATCH Toggle Product Status',
            'description' => "## PATCH /super-admin/supplier-products/{id}/toggle-status",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('DELETE', 'super-admin/supplier-products/{{supplier_product_id}}') => [
            'method' => 'DELETE', 'path' => 'super-admin/supplier-products/{{supplier_product_id}}', 'name' => 'DELETE Supplier Product',
            'description' => "## DELETE /super-admin/supplier-products/{supplierProduct}",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Super Admin Orders (2) ──
        endpointKey('GET', 'super-admin/orders') => [
            'method' => 'GET', 'path' => 'super-admin/orders', 'name' => 'GET List Orders',
            'description' => "## GET /super-admin/orders\n\n**Query:** status, company_id, customer_id, from, to, per_page",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => ['per_page' => '15'], 'event' => null,
        ],
        endpointKey('GET', 'super-admin/orders/{{order_id}}') => [
            'method' => 'GET', 'path' => 'super-admin/orders/{{order_id}}', 'name' => 'GET Show Order',
            'description' => "## GET /super-admin/orders/{order}\n\nitems + source + statusHistory",
            'headers' => $h($accept, $a('super_admin_token')), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Company Auth (3) ──
        endpointKey('POST', 'company/login') => [
            'method' => 'POST', 'path' => 'company/login', 'name' => 'POST Login',
            'description' => "## POST /company/login\n\n**Body:** tax_number, password\n\n**Response:** `{ token, company }`",
            'headers' => $h($accept, $json),
            'body' => $rb("{\n  \"tax_number\": \"{{company_tax_number}}\",\n  \"password\": \"{{company_password}}\"\n}"),
            'query' => [], 'event' => saveToken('company_token'),
        ],
        endpointKey('POST', 'company/logout') => [
            'method' => 'POST', 'path' => 'company/logout', 'name' => 'POST Logout',
            'description' => "## POST /company/logout",
            'headers' => $h($accept, $a('company_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('GET', 'company/me') => [
            'method' => 'GET', 'path' => 'company/me', 'name' => 'GET Me',
            'description' => "## GET /company/me\n\n**Response:** company + wallet_balance",
            'headers' => $h($accept, $a('company_token')), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Company Products (5) ──
        endpointKey('GET', 'company/products') => [
            'method' => 'GET', 'path' => 'company/products', 'name' => 'GET List Products',
            'description' => "## GET /company/products",
            'headers' => $h($accept, $a('company_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'company/products') => [
            'method' => 'POST', 'path' => 'company/products', 'name' => 'POST Create Product',
            'description' => "## POST /company/products\n\n**Body:** name, cash_price, is_active, installment_plans[] (optional), description, image",
            'headers' => $h($accept, $a('company_token'), $json),
            'body' => $rb("{\n  \"name\": \"منتج\",\n  \"cash_price\": 1500,\n  \"is_active\": true,\n  \"installment_plans\": [{ \"months\": 6, \"down_payment\": 300, \"installment_amount\": 250 }]\n}"),
            'query' => [], 'event' => saveIdFromData('product_id'),
        ],
        endpointKey('GET', 'company/products/{{product_id}}') => [
            'method' => 'GET', 'path' => 'company/products/{{product_id}}', 'name' => 'GET Show Product',
            'description' => "## GET /company/products/{companyProduct}",
            'headers' => $h($accept, $a('company_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'company/products/{{product_id}}') => [
            'method' => 'POST', 'path' => 'company/products/{{product_id}}', 'name' => 'POST Update Product',
            'description' => "## POST /company/products/{companyProduct}\n\nPOST وليس PUT — multipart للصورة",
            'headers' => $h($accept, $a('company_token'), $json),
            'body' => $rb("{\n  \"name\": \"منتج محدّث\",\n  \"cash_price\": 1200\n}"), 'query' => [], 'event' => null,
        ],
        endpointKey('DELETE', 'company/products/{{product_id}}') => [
            'method' => 'DELETE', 'path' => 'company/products/{{product_id}}', 'name' => 'DELETE Product',
            'description' => "## DELETE /company/products/{companyProduct}",
            'headers' => $h($accept, $a('company_token')), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Company Catalog (6) ──
        endpointKey('GET', 'company/catalog/available') => [
            'method' => 'GET', 'path' => 'company/catalog/available', 'name' => 'GET Available Catalog',
            'description' => "## GET /company/catalog/available\n\nمنتجات الموردين المتاحة للإضافة",
            'headers' => $h($accept, $a('company_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('GET', 'company/catalog/mine') => [
            'method' => 'GET', 'path' => 'company/catalog/mine', 'name' => 'GET My Catalog',
            'description' => "## GET /company/catalog/mine",
            'headers' => $h($accept, $a('company_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'company/catalog/add') => [
            'method' => 'POST', 'path' => 'company/catalog/add', 'name' => 'POST Add to Catalog (bulk)',
            'description' => "## POST /company/catalog/add\n\n**Body:** `{ product_ids: [1,2] }`",
            'headers' => $h($accept, $a('company_token'), $json),
            'body' => $rb("{\n  \"product_ids\": [{{supplier_product_id}}]\n}"), 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'company/catalog/remove') => [
            'method' => 'POST', 'path' => 'company/catalog/remove', 'name' => 'POST Remove from Catalog (bulk)',
            'description' => "## POST /company/catalog/remove\n\n**Body:** `{ product_ids: [1,2] }`",
            'headers' => $h($accept, $a('company_token'), $json),
            'body' => $rb("{\n  \"product_ids\": [{{supplier_product_id}}]\n}"), 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'company/catalog/{{supplier_product_id}}') => [
            'method' => 'POST', 'path' => 'company/catalog/{{supplier_product_id}}', 'name' => 'POST Add Single to Catalog',
            'description' => "## POST /company/catalog/{supplierProduct}",
            'headers' => $h($accept, $a('company_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('DELETE', 'company/catalog/{{supplier_product_id}}') => [
            'method' => 'DELETE', 'path' => 'company/catalog/{{supplier_product_id}}', 'name' => 'DELETE Remove from Catalog',
            'description' => "## DELETE /company/catalog/{supplierProduct}",
            'headers' => $h($accept, $a('company_token')), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Company Finance (2) ──
        endpointKey('GET', 'company/wallet/transactions') => [
            'method' => 'GET', 'path' => 'company/wallet/transactions', 'name' => 'GET Wallet Transactions',
            'description' => "## GET /company/wallet/transactions\n\n**Query:** direction, from, to, per_page",
            'headers' => $h($accept, $a('company_token')), 'body' => null, 'query' => ['per_page' => '15'], 'event' => null,
        ],
        endpointKey('POST', 'company/wallet/withdrawals') => [
            'method' => 'POST', 'path' => 'company/wallet/withdrawals', 'name' => 'POST Request Withdrawal',
            'description' => "## POST /company/wallet/withdrawals\n\n**Body:** amount (min 100), idempotency_key\n\nيحجز الرصيد (withdrawal_hold)",
            'headers' => $h($accept, $a('company_token'), $json),
            'body' => $rb("{\n  \"amount\": 200,\n  \"idempotency_key\": \"wd-001\"\n}"),
            'query' => [], 'event' => saveIdFromData('withdrawal_request_id'),
        ],

        // ── Company Customers (1) ──
        endpointKey('GET', 'company/customers') => [
            'method' => 'GET', 'path' => 'company/customers', 'name' => 'GET List Customers',
            'description' => "## GET /company/customers\n\n**Query:** status (active|inactive), search, per_page",
            'headers' => $h($accept, $a('company_token')),
            'body' => null, 'query' => ['per_page' => '15', 'status' => ['key' => 'status', 'value' => 'active', 'disabled' => true], 'search' => ['key' => 'search', 'value' => '010', 'disabled' => true]],
            'event' => null,
        ],

        // ── Company Orders (4) ──
        endpointKey('GET', 'company/orders') => [
            'method' => 'GET', 'path' => 'company/orders', 'name' => 'GET List Orders',
            'description' => "## GET /company/orders\n\n**Query:** status, customer_id, from, to, per_page",
            'headers' => $h($accept, $a('company_token')), 'body' => null, 'query' => ['per_page' => '15'], 'event' => null,
        ],
        endpointKey('POST', 'company/orders') => [
            'method' => 'POST', 'path' => 'company/orders', 'name' => 'POST Create Order',
            'description' => "## POST /company/orders\n\n**Body:** customer_id, payment_type (cash|installment), items[], discount, notes, source, idempotency_key\n\n**cash:** بدون installment_plan\n\n**installment:** منتج واحد + installment_plan { months, down_payment, installment_amount } من خطط المنتج\n\n**source.channel:** ad | referral | link | direct",
            'headers' => $h($accept, $a('company_token'), $json),
            'body' => $rb("{\n  \"customer_id\": {{customer_id}},\n  \"payment_type\": \"cash\",\n  \"items\": [{ \"company_product_id\": {{product_id}}, \"quantity\": 1 }],\n  \"source\": { \"channel\": \"direct\" },\n  \"idempotency_key\": \"co-001\"\n}"),
            'query' => [], 'event' => saveIdFromData('order_id'),
        ],
        endpointKey('GET', 'company/orders/{{order_id}}') => [
            'method' => 'GET', 'path' => 'company/orders/{{order_id}}', 'name' => 'GET Show Order',
            'description' => "## GET /company/orders/{order}",
            'headers' => $h($accept, $a('company_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('PATCH', 'company/orders/{{order_id}}/status') => [
            'method' => 'PATCH', 'path' => 'company/orders/{{order_id}}/status', 'name' => 'PATCH Update Order Status',
            'description' => "## PATCH /company/orders/{order}/status\n\n**Body:** status (processing|completed|cancelled), note, cancellation_reason (required if cancelled)\n\n**completed** → CommissionService (5%)",
            'headers' => $h($accept, $a('company_token'), $json),
            'body' => $rb("{\n  \"status\": \"completed\",\n  \"note\": \"تم التسليم\"\n}"), 'query' => [], 'event' => null,
        ],

        // ── Customer Auth Plan 2 (5) ──
        endpointKey('POST', 'customer/register') => [
            'method' => 'POST', 'path' => 'customer/register', 'name' => 'POST Register',
            'description' => "## POST /customer/register\n\n**Body:** phone, password, password_confirmation, full_name, governorate_id, city, address, email, company_id (optional)",
            'headers' => $h($accept, $json),
            'body' => $rb("{\n  \"phone\": \"{{customer_phone}}\",\n  \"password\": \"{{customer_password}}\",\n  \"password_confirmation\": \"{{customer_password}}\",\n  \"full_name\": \"أحمد محمد\",\n  \"governorate_id\": {{governorate_id}},\n  \"company_id\": {{company_id}}\n}"),
            'query' => [], 'event' => array_merge(saveToken('customer_token'), saveIdFromData('customer_id')),
        ],
        endpointKey('POST', 'customer/login') => [
            'method' => 'POST', 'path' => 'customer/login', 'name' => 'POST Login',
            'description' => "## POST /customer/login\n\n**Body:** phone, password",
            'headers' => $h($accept, $json),
            'body' => $rb("{\n  \"phone\": \"{{customer_phone}}\",\n  \"password\": \"{{customer_password}}\"\n}"),
            'query' => [], 'event' => saveToken('customer_token'),
        ],
        endpointKey('POST', 'customer/logout') => [
            'method' => 'POST', 'path' => 'customer/logout', 'name' => 'POST Logout',
            'description' => "## POST /customer/logout",
            'headers' => $h($accept, $a('customer_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('GET', 'customer/me') => [
            'method' => 'GET', 'path' => 'customer/me', 'name' => 'GET Me',
            'description' => "## GET /customer/me\n\n**Response:** `{ data: CustomerResource }`",
            'headers' => $h($accept, $a('customer_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('PATCH', 'customer/profile') => [
            'method' => 'PATCH', 'path' => 'customer/profile', 'name' => 'PATCH Update Profile',
            'description' => "## PATCH /customer/profile\n\n**Body (all optional):** email, full_name, governorate_id, city, address, date_of_birth, gender",
            'headers' => $h($accept, $a('customer_token'), $json),
            'body' => $rb("{\n  \"full_name\": \"أحمد علي\",\n  \"city\": \"الجيزة\"\n}"), 'query' => [], 'event' => null,
        ],

        // ── Customer OTP (3) ──
        endpointKey('POST', 'customer/auth/check-phone') => [
            'method' => 'POST', 'path' => 'customer/auth/check-phone', 'name' => 'POST Check Phone',
            'description' => "## POST /customer/auth/check-phone\n\n**Body:** phone\n\n**Response:** `{ exists: true|false }`",
            'headers' => $h($accept, $json),
            'body' => $rb("{\n  \"phone\": \"{{customer_phone}}\"\n}"), 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'customer/register/request-otp') => [
            'method' => 'POST', 'path' => 'customer/register/request-otp', 'name' => 'POST Request OTP',
            'description' => "## POST /customer/register/request-otp\n\n**Body:** phone\n\nفي APP_DEBUG=true يرجع debug_otp",
            'headers' => $h($accept, $json),
            'body' => $rb("{\n  \"phone\": \"{{customer_phone}}\"\n}"), 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'customer/register/verify') => [
            'method' => 'POST', 'path' => 'customer/register/verify', 'name' => 'POST Verify Registration',
            'description' => "## POST /customer/register/verify\n\n**Body:** phone, otp, name, password, password_confirmation, governorate_id",
            'headers' => $h($accept, $json),
            'body' => $rb("{\n  \"phone\": \"{{customer_phone}}\",\n  \"otp\": \"{{customer_otp}}\",\n  \"name\": \"محمد أحمد\",\n  \"password\": \"{{customer_password}}\",\n  \"password_confirmation\": \"{{customer_password}}\",\n  \"governorate_id\": {{governorate_id}}\n}"),
            'query' => [], 'event' => saveToken('customer_token'),
        ],

        // ── Customer Orders (3) ──
        endpointKey('GET', 'customer/orders') => [
            'method' => 'GET', 'path' => 'customer/orders', 'name' => 'GET List Orders',
            'description' => "## GET /customer/orders\n\n**Query:** status, company_id, from, to, per_page",
            'headers' => $h($accept, $a('customer_token')), 'body' => null, 'query' => ['per_page' => '15'], 'event' => null,
        ],
        endpointKey('POST', 'customer/orders') => [
            'method' => 'POST', 'path' => 'customer/orders', 'name' => 'POST Create Order',
            'description' => "## POST /customer/orders\n\n**Body:** company_id, payment_type, items[], source, idempotency_key\n\n**cash:** payment_type=cash بدون installment_plan\n\n**installment:** payment_type=installment + installment_plan { months, down_payment, installment_amount } + منتج واحد\n\nيتطلب ربط active مع الشركة",
            'headers' => $h($accept, $a('customer_token'), $json),
            'body' => $rb("{\n  \"company_id\": {{company_id}},\n  \"payment_type\": \"cash\",\n  \"items\": [{ \"company_product_id\": {{product_id}}, \"quantity\": 1 }],\n  \"source\": { \"channel\": \"link\" },\n  \"idempotency_key\": \"cu-001\"\n}"),
            'query' => [], 'event' => saveIdFromData('order_id'),
        ],
        endpointKey('GET', 'customer/orders/{{order_id}}') => [
            'method' => 'GET', 'path' => 'customer/orders/{{order_id}}', 'name' => 'GET Show Order',
            'description' => "## GET /customer/orders/{order}",
            'headers' => $h($accept, $a('customer_token')), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Customer Maintenance (3) ──
        endpointKey('GET', 'customer/maintenance-requests') => [
            'method' => 'GET', 'path' => 'customer/maintenance-requests', 'name' => 'GET List Maintenance Requests',
            'description' => "## GET /customer/maintenance-requests",
            'headers' => $h($accept, $a('customer_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'customer/maintenance-requests') => [
            'method' => 'POST', 'path' => 'customer/maintenance-requests', 'name' => 'POST Create Maintenance Request',
            'description' => "## POST /customer/maintenance-requests\n\n**Auth:** Bearer customer_token\n\n**Body (JSON):**\n- company_id, full_name, phone\n- governorate_id, city, area, address_details (optional)\n- device_details, purification_system, stages_count\n- last_stage_change_dates: { stage_1..stage_N } حسب stages_count\n- primary_problem_type, malfunction_type, notes (optional)\n\n**Lookups:** GET /public/maintenance/lookups\n\n**multipart (optional):** + image (jpeg/png/webp max 2MB)",
            'headers' => $h($accept, $a('customer_token'), $json),
            'body' => $rb("{\n  \"company_id\": {{company_id}},\n  \"full_name\": \"أحمد محمد\",\n  \"phone\": \"01012345678\",\n  \"governorate_id\": {{governorate_id}},\n  \"city\": \"مدينة نصر\",\n  \"area\": \"الحي السابع\",\n  \"address_details\": \"شارع 9\",\n  \"device_details\": \"فلتر 7 مراحل\",\n  \"purification_system\": \"ro\",\n  \"stages_count\": 3,\n  \"last_stage_change_dates\": {\n    \"stage_1\": \"2025-01-15\",\n    \"stage_2\": \"2025-02-15\",\n    \"stage_3\": \"2025-03-15\"\n  },\n  \"primary_problem_type\": \"water_quality\",\n  \"malfunction_type\": \"stage_1\",\n  \"notes\": \"المياه بطيئة\"\n}"),
            'query' => [], 'event' => saveIdFromData('maintenance_request_id'),
        ],
        endpointKey('GET', 'customer/maintenance-requests/{{maintenance_request_id}}') => [
            'method' => 'GET', 'path' => 'customer/maintenance-requests/{{maintenance_request_id}}', 'name' => 'GET Show Maintenance Request',
            'description' => "## GET /customer/maintenance-requests/{maintenanceRequest}",
            'headers' => $h($accept, $a('customer_token')), 'body' => null, 'query' => [], 'event' => null,
        ],

        // ── Customer Engagement (4) ──
        endpointKey('POST', 'customer/companies/{{company_id}}/like') => [
            'method' => 'POST', 'path' => 'customer/companies/{{company_id}}/like', 'name' => 'POST Like Company',
            'description' => "## POST /customer/companies/{company}/like",
            'headers' => $h($accept, $a('customer_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('DELETE', 'customer/companies/{{company_id}}/like') => [
            'method' => 'DELETE', 'path' => 'customer/companies/{{company_id}}/like', 'name' => 'DELETE Unlike Company',
            'description' => "## DELETE /customer/companies/{company}/like",
            'headers' => $h($accept, $a('customer_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
        endpointKey('POST', 'customer/companies/{{company_id}}/rating') => [
            'method' => 'POST', 'path' => 'customer/companies/{{company_id}}/rating', 'name' => 'POST Rate Company',
            'description' => "## POST /customer/companies/{company}/rating\n\n**Body:** rating (1-5), comment (optional)",
            'headers' => $h($accept, $a('customer_token'), $json),
            'body' => $rb("{\n  \"rating\": 5,\n  \"comment\": \"خدمة ممتازة\"\n}"), 'query' => [], 'event' => null,
        ],
        endpointKey('DELETE', 'customer/companies/{{company_id}}/rating') => [
            'method' => 'DELETE', 'path' => 'customer/companies/{{company_id}}/rating', 'name' => 'DELETE Remove Rating',
            'description' => "## DELETE /customer/companies/{company}/rating",
            'headers' => $h($accept, $a('customer_token')), 'body' => null, 'query' => [], 'event' => null,
        ],
    ];

    return $list;
}

function buildRequestFromEndpoint(array $ep): array
{
    return req(
        $ep['method'],
        $ep['path'],
        $ep['description'],
        $ep['headers'],
        $ep['body'],
        $ep['query'],
        $ep['event']
    );
}

function buildEndpointFolder(string $name, string $description, array $keys, array $endpoints): array
{
    $items = [];

    foreach ($keys as $key) {
        if (! isset($endpoints[$key])) {
            throw new RuntimeException("Missing endpoint: {$key}");
        }
        $ep      = $endpoints[$key];
        $items[] = named($ep['name'], buildRequestFromEndpoint($ep));
    }

    return folder($name, $description, $items);
}

$endpoints = allEndpoints();
$total     = count($endpoints);

$endpointIndex = '';

foreach ($endpoints as $key => $ep) {
    $endpointIndex .= "- `{$ep['method']} /{$ep['path']}` — {$ep['name']}\n";
}

$collection = [
    'info' => [
        'name'        => 'Watfil API — Complete',
        '_postman_id' => 'watfil-api-complete-2026-v3',
        'description' => <<<MD
# Watfil API — {$total} Endpoint

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## البداية السريعة

```
php artisan migrate --seed
php artisan serve
```

1. Import هذا الملف في Postman
2. شغّل **00 — Setup Flow** (11 خطوة)
3. استخدم مجلد **All Endpoints** لأي endpoint

**Super Admin:** `admin@watafl.com` / `Admin@1234`

---

## Headers

| Header | القيمة |
|--------|--------|
| Accept | application/json |
| Authorization | Bearer `{token}` |
| Content-Type | application/json |

---

## شكل الاستجابة

- **قائمة:** `{ data: [], meta: { total, current_page, last_page, per_page } }`
- **طفرات:** `{ message, data }`
- **Auth:** `{ message, token, customer }`
- **422:** `{ message, errors: { field: [] } }`

---

## فهرس كل الـ Endpoints ({$total})

{$endpointIndex}

---

## سيناريوهات

**طلب + عمولة:** pending → processing → completed (5% إلا referral/internal)

**سحب:** request → approve → pay (أو reject + reason)

**عميل:** register + company_id للربط بالشركة
MD,
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'base_url', 'value' => 'http://localhost:8000/api', 'type' => 'string'],
        ['key' => 'super_admin_token', 'value' => '', 'type' => 'string'],
        ['key' => 'company_token', 'value' => '', 'type' => 'string'],
        ['key' => 'company_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'governorate_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'supplier_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'supplier_product_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'product_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'product_type_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'category_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'withdrawal_request_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'maintenance_request_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'company_tax_number', 'value' => 'TAX-TEST-001', 'type' => 'string'],
        ['key' => 'company_password', 'value' => 'Company@1234', 'type' => 'string'],
        ['key' => 'customer_token', 'value' => '', 'type' => 'string'],
        ['key' => 'customer_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'customer_phone', 'value' => '01012345678', 'type' => 'string'],
        ['key' => 'customer_password', 'value' => 'Customer@1234', 'type' => 'string'],
        ['key' => 'order_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'customer_otp', 'value' => '123456', 'type' => 'string'],
    ],
    'item' => [],
];

// Setup Flow
$setup = folder('00 — Setup Flow', "إعداد بيئة اختبار — شغّل بالترتيب\n\nيملأ: super_admin_token, company_id, governorate_id, supplier_id, supplier_product_id, product_id, customer_id, customer_token, order_id", [
    named('1. Super Admin Login', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'super-admin/login')])),
    named('2. Get Governorates', array_replace(buildRequestFromEndpoint($endpoints[endpointKey('GET', 'super-admin/governorates')]), [
        'event' => [[
            'listen' => 'test', 'script' => ['type' => 'text/javascript', 'exec' => [
                "var res = pm.response.json();",
                "if (res.data && res.data[0]) pm.collectionVariables.set('governorate_id', String(res.data[0].id));",
            ]],
        ]],
    ])),
    named('3. Create Company', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'super-admin/companies')])),
    named('4. Credit Wallet', req('POST', 'super-admin/companies/{{company_id}}/wallet/adjust', $endpoints[endpointKey('POST', 'super-admin/companies/{{company_id}}/wallet/adjust')]['description'], $endpoints[endpointKey('POST', 'super-admin/companies/{{company_id}}/wallet/adjust')]['headers'], rawBody("{\n  \"amount\": 1000,\n  \"type\": \"credit\",\n  \"reason\": \"رصيد افتتاحي\",\n  \"idempotency_key\": \"setup-credit\"\n}"))),
    named('5. Create Supplier', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'super-admin/suppliers')])),
    named('6. Create Supplier Product', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'super-admin/supplier-products')])),
    named('7. Company Login', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'company/login')])),
    named('8. Add to Catalog', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'company/catalog/add')])),
    named('9. Create Company Product', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'company/products')])),
    named('10. Register Customer', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'customer/register')])),
    named('11. Company Create Order', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'company/orders')])),
]);

$withdrawalFlow = folder('01 — Withdrawal Flow', 'سيناريو سحب كامل', [
    named('1. Company Login', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'company/login')])),
    named('2. Request Withdrawal', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'company/wallet/withdrawals')])),
    named('3. Admin Login', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'super-admin/login')])),
    named('4. List Pending', buildRequestFromEndpoint($endpoints[endpointKey('GET', 'super-admin/finance/withdrawal-requests')])),
    named('5. Approve', buildRequestFromEndpoint($endpoints[endpointKey('PATCH', 'super-admin/finance/withdrawal-requests/{{withdrawal_request_id}}/approve')])),
    named('6. Pay', buildRequestFromEndpoint($endpoints[endpointKey('PATCH', 'super-admin/finance/withdrawal-requests/{{withdrawal_request_id}}/pay')])),
    named('7. Transactions', buildRequestFromEndpoint($endpoints[endpointKey('GET', 'company/wallet/transactions')])),
]);

$customerFlow = folder('02 — Customer Flow', 'تسجيل عميل + ملف + عملاء الشركة', [
    named('1. Register', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'customer/register')])),
    named('2. Me', buildRequestFromEndpoint($endpoints[endpointKey('GET', 'customer/me')])),
    named('3. Update Profile', buildRequestFromEndpoint($endpoints[endpointKey('PATCH', 'customer/profile')])),
    named('4. Company Login', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'company/login')])),
    named('5. List Company Customers', buildRequestFromEndpoint($endpoints[endpointKey('GET', 'company/customers')])),
]);

$orderFlow = folder('03 — Order Flow', 'طلب → processing → completed + عمولة', [
    named('1. Company Login', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'company/login')])),
    named('2. Create Order', buildRequestFromEndpoint($endpoints[endpointKey('POST', 'company/orders')])),
    named('3. Status → processing', req('PATCH', 'company/orders/{{order_id}}/status', $endpoints[endpointKey('PATCH', 'company/orders/{{order_id}}/status')]['description'], $endpoints[endpointKey('PATCH', 'company/orders/{{order_id}}/status')]['headers'], rawBody("{\n  \"status\": \"processing\"\n}"))),
    named('4. Status → completed', buildRequestFromEndpoint($endpoints[endpointKey('PATCH', 'company/orders/{{order_id}}/status')])),
    named('5. Customer List Orders', buildRequestFromEndpoint($endpoints[endpointKey('GET', 'customer/orders')])),
    named('6. Admin List Orders', buildRequestFromEndpoint($endpoints[endpointKey('GET', 'super-admin/orders')])),
]);

$publicFlow = folder('04 — Public Store Flow', 'تصفح بدون تسجيل', [
    named('1. Governorates', buildRequestFromEndpoint($endpoints[endpointKey('GET', 'public/governorates')])),
    named('2. Maintenance Lookups', buildRequestFromEndpoint($endpoints[endpointKey('GET', 'public/maintenance/lookups')])),
    named('3. Companies', buildRequestFromEndpoint($endpoints[endpointKey('GET', 'public/companies')])),
    named('4. Company Details', buildRequestFromEndpoint($endpoints[endpointKey('GET', 'public/companies/{{company_id}}')])),
    named('5. Store Products', buildRequestFromEndpoint($endpoints[endpointKey('GET', 'public/companies/{{company_id}}/products')])),
    named('6. Product Installment Plans', buildRequestFromEndpoint($endpoints[endpointKey('GET', 'public/companies/{{company_id}}/products/{{product_id}}/installment-plans')])),
]);

$allEndpointsFolder = folder('All Endpoints', "كل الـ {$total} endpoint — واحد لكل route في routes/api.php\n\nافتح Description لأي request للتفاصيل الكاملة", [
    buildEndpointFolder('Public (8)', 'بدون auth', [
        endpointKey('GET', 'public/governorates'),
        endpointKey('GET', 'public/product-types'),
        endpointKey('GET', 'public/categories'),
        endpointKey('GET', 'public/maintenance/lookups'),
        endpointKey('GET', 'public/companies'),
        endpointKey('GET', 'public/companies/{{company_id}}'),
        endpointKey('GET', 'public/companies/{{company_id}}/products'),
        endpointKey('GET', 'public/companies/{{company_id}}/products/{{product_id}}/installment-plans'),
    ], $endpoints),
    buildEndpointFolder('Super Admin — Auth (3)', '', [
        endpointKey('POST', 'super-admin/login'),
        endpointKey('POST', 'super-admin/logout'),
        endpointKey('GET', 'super-admin/me'),
    ], $endpoints),
    buildEndpointFolder('Super Admin — Governorates (1)', '', [endpointKey('GET', 'super-admin/governorates')], $endpoints),
    buildEndpointFolder('Super Admin — Product Types (5)', '', [
        endpointKey('GET', 'super-admin/product-types'),
        endpointKey('POST', 'super-admin/product-types'),
        endpointKey('GET', 'super-admin/product-types/{{product_type_id}}'),
        endpointKey('POST', 'super-admin/product-types/{{product_type_id}}'),
        endpointKey('DELETE', 'super-admin/product-types/{{product_type_id}}'),
    ], $endpoints),
    buildEndpointFolder('Super Admin — Categories (5)', '', [
        endpointKey('GET', 'super-admin/categories'),
        endpointKey('POST', 'super-admin/categories'),
        endpointKey('GET', 'super-admin/categories/{{category_id}}'),
        endpointKey('POST', 'super-admin/categories/{{category_id}}'),
        endpointKey('DELETE', 'super-admin/categories/{{category_id}}'),
    ], $endpoints),
    buildEndpointFolder('Super Admin — Companies (6)', '', [
        endpointKey('GET', 'super-admin/companies'),
        endpointKey('POST', 'super-admin/companies'),
        endpointKey('GET', 'super-admin/companies/{{company_id}}'),
        endpointKey('POST', 'super-admin/companies/{{company_id}}'),
        endpointKey('PATCH', 'super-admin/companies/{{company_id}}/toggle-status'),
        endpointKey('DELETE', 'super-admin/companies/{{company_id}}'),
    ], $endpoints),
    buildEndpointFolder('Super Admin — Company Wallet (5)', '', [
        endpointKey('GET', 'super-admin/companies/{{company_id}}/wallet'),
        endpointKey('PATCH', 'super-admin/companies/{{company_id}}/wallet'),
        endpointKey('POST', 'super-admin/companies/{{company_id}}/wallet/adjust'),
        endpointKey('GET', 'super-admin/companies/{{company_id}}/wallet/transactions'),
    ], $endpoints),
    buildEndpointFolder('Super Admin — Finance (5)', '', [
        endpointKey('GET', 'super-admin/finance/commissions/summary'),
        endpointKey('GET', 'super-admin/finance/withdrawal-requests'),
        endpointKey('PATCH', 'super-admin/finance/withdrawal-requests/{{withdrawal_request_id}}/approve'),
        endpointKey('PATCH', 'super-admin/finance/withdrawal-requests/{{withdrawal_request_id}}/reject'),
        endpointKey('PATCH', 'super-admin/finance/withdrawal-requests/{{withdrawal_request_id}}/pay'),
    ], $endpoints),
    buildEndpointFolder('Super Admin — Suppliers (5)', '', [
        endpointKey('GET', 'super-admin/suppliers'),
        endpointKey('POST', 'super-admin/suppliers'),
        endpointKey('GET', 'super-admin/suppliers/{{supplier_id}}'),
        endpointKey('POST', 'super-admin/suppliers/{{supplier_id}}'),
        endpointKey('DELETE', 'super-admin/suppliers/{{supplier_id}}'),
    ], $endpoints),
    buildEndpointFolder('Super Admin — Supplier Products (6)', '', [
        endpointKey('GET', 'super-admin/supplier-products'),
        endpointKey('POST', 'super-admin/supplier-products'),
        endpointKey('GET', 'super-admin/supplier-products/{{supplier_product_id}}'),
        endpointKey('POST', 'super-admin/supplier-products/{{supplier_product_id}}'),
        endpointKey('PATCH', 'super-admin/supplier-products/{{supplier_product_id}}/toggle-status'),
        endpointKey('DELETE', 'super-admin/supplier-products/{{supplier_product_id}}'),
    ], $endpoints),
    buildEndpointFolder('Super Admin — Orders (2)', '', [
        endpointKey('GET', 'super-admin/orders'),
        endpointKey('GET', 'super-admin/orders/{{order_id}}'),
    ], $endpoints),
    buildEndpointFolder('Company — Auth (3)', '', [
        endpointKey('POST', 'company/login'),
        endpointKey('POST', 'company/logout'),
        endpointKey('GET', 'company/me'),
    ], $endpoints),
    buildEndpointFolder('Company — Products (5)', '', [
        endpointKey('GET', 'company/products'),
        endpointKey('POST', 'company/products'),
        endpointKey('GET', 'company/products/{{product_id}}'),
        endpointKey('POST', 'company/products/{{product_id}}'),
        endpointKey('DELETE', 'company/products/{{product_id}}'),
    ], $endpoints),
    buildEndpointFolder('Company — Catalog (6)', '', [
        endpointKey('GET', 'company/catalog/available'),
        endpointKey('GET', 'company/catalog/mine'),
        endpointKey('POST', 'company/catalog/add'),
        endpointKey('POST', 'company/catalog/remove'),
        endpointKey('POST', 'company/catalog/{{supplier_product_id}}'),
        endpointKey('DELETE', 'company/catalog/{{supplier_product_id}}'),
    ], $endpoints),
    buildEndpointFolder('Company — Finance (2)', '', [
        endpointKey('GET', 'company/wallet/transactions'),
        endpointKey('POST', 'company/wallet/withdrawals'),
    ], $endpoints),
    buildEndpointFolder('Company — Customers (1)', '', [endpointKey('GET', 'company/customers')], $endpoints),
    buildEndpointFolder('Company — Orders (4)', '', [
        endpointKey('GET', 'company/orders'),
        endpointKey('POST', 'company/orders'),
        endpointKey('GET', 'company/orders/{{order_id}}'),
        endpointKey('PATCH', 'company/orders/{{order_id}}/status'),
    ], $endpoints),
    buildEndpointFolder('Customer — Auth (5)', 'Register / Login / Profile', [
        endpointKey('POST', 'customer/register'),
        endpointKey('POST', 'customer/login'),
        endpointKey('POST', 'customer/logout'),
        endpointKey('GET', 'customer/me'),
        endpointKey('PATCH', 'customer/profile'),
    ], $endpoints),
    buildEndpointFolder('Customer — OTP (3)', 'مسار الموبايل', [
        endpointKey('POST', 'customer/auth/check-phone'),
        endpointKey('POST', 'customer/register/request-otp'),
        endpointKey('POST', 'customer/register/verify'),
    ], $endpoints),
    buildEndpointFolder('Customer — Orders (3)', '', [
        endpointKey('GET', 'customer/orders'),
        endpointKey('POST', 'customer/orders'),
        endpointKey('GET', 'customer/orders/{{order_id}}'),
    ], $endpoints),
    buildEndpointFolder('Customer — Maintenance (3)', '', [
        endpointKey('GET', 'customer/maintenance-requests'),
        endpointKey('POST', 'customer/maintenance-requests'),
        endpointKey('GET', 'customer/maintenance-requests/{{maintenance_request_id}}'),
    ], $endpoints),
    buildEndpointFolder('Customer — Engagement (4)', 'Like & Rating', [
        endpointKey('POST', 'customer/companies/{{company_id}}/like'),
        endpointKey('DELETE', 'customer/companies/{{company_id}}/like'),
        endpointKey('POST', 'customer/companies/{{company_id}}/rating'),
        endpointKey('DELETE', 'customer/companies/{{company_id}}/rating'),
    ], $endpoints),
]);

$collection['item'] = [
    $setup,
    $withdrawalFlow,
    $customerFlow,
    $orderFlow,
    $publicFlow,
    $allEndpointsFolder,
];

$out = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($out === false) {
    fwrite(STDERR, "JSON encode failed\n");
    exit(1);
}

$target = dirname(__DIR__) . '/Watfil_API_Complete.postman_collection.json';
file_put_contents($target, $out . "\n");

echo "Written: {$target}\n";
echo "Total unique endpoints: {$total}\n";
