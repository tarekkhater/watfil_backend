<?php

namespace Tests\Feature;

use App\Models\CommissionRule;
use App\Models\Company;
use App\Models\CompanyProduct;
use App\Models\Customer;
use App\Models\CustomerCompanyLink;
use App\Models\CustomerProfile;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\SuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_create_and_complete_order(): void
    {
        [$company, $customer, $product] = $this->createFixture();

        CommissionRule::create([
            'name'             => 'Default order commission',
            'trigger'          => 'order_completed',
            'calculation_type' => 'percentage',
            'amount'           => 5,
            'priority'         => 1,
            'is_active'        => true,
        ]);

        Sanctum::actingAs($company);

        $createResponse = $this->postJson('/api/company/orders', [
            'customer_id'  => $customer->id,
            'payment_type' => 'cash',
            'items'        => [
                ['company_product_id' => $product->id, 'quantity' => 1],
            ],
            'source' => ['channel' => 'link'],
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_amount', 1000)
            ->assertJsonPath('data.source.channel', 'link');

        $orderId = $createResponse->json('data.id');

        $this->patchJson("/api/company/orders/{$orderId}/status", [
            'status' => 'processing',
        ])->assertOk()
            ->assertJsonPath('data.status', 'processing');

        $this->patchJson("/api/company/orders/{$orderId}/status", [
            'status' => 'completed',
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $company->refresh();
        $this->assertSame(950.0, (float) $company->wallet_balance);

        $this->getJson('/api/company/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_customer_can_create_order_for_linked_company(): void
    {
        [$company, $customer, $product] = $this->createFixture();

        Sanctum::actingAs($customer);

        $this->postJson('/api/customer/orders', [
            'company_id'   => $company->id,
            'payment_type' => 'cash',
            'items'        => [
                ['company_product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.total_amount', 2000)
            ->assertJsonPath('data.source.channel', 'direct');

        $this->getJson('/api/customer/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_customer_cannot_view_other_customers_order(): void
    {
        [$company, $customer, $product] = $this->createFixture();
        $otherCustomer = $this->createCustomer('01080808080');

        CustomerCompanyLink::create([
            'customer_id' => $otherCustomer->id,
            'company_id'  => $company->id,
            'status'      => 'active',
            'linked_at'   => now(),
        ]);

        Sanctum::actingAs($company);

        $orderId = $this->postJson('/api/company/orders', [
            'customer_id'  => $customer->id,
            'payment_type' => 'cash',
            'items'        => [
                ['company_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->json('data.id');

        Sanctum::actingAs($otherCustomer);

        $this->getJson("/api/customer/orders/{$orderId}")
            ->assertForbidden();
    }

    public function test_super_admin_can_monitor_orders(): void
    {
        [$company, $customer, $product] = $this->createFixture();
        $admin = $this->createSuperAdmin();

        Sanctum::actingAs($company);

        $orderId = $this->postJson('/api/company/orders', [
            'customer_id'  => $customer->id,
            'payment_type' => 'cash',
            'items'        => [
                ['company_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->json('data.id');

        Sanctum::actingAs($admin);

        $this->getJson('/api/super-admin/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson("/api/super-admin/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('data.id', $orderId);
    }

    public function test_cancelling_order_requires_reason(): void
    {
        [$company, $customer, $product] = $this->createFixture();

        Sanctum::actingAs($company);

        $orderId = $this->postJson('/api/company/orders', [
            'customer_id'  => $customer->id,
            'payment_type' => 'cash',
            'items'        => [
                ['company_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->json('data.id');

        $this->patchJson("/api/company/orders/{$orderId}/status", [
            'status' => 'cancelled',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['cancellation_reason']);

        $this->patchJson("/api/company/orders/{$orderId}/status", [
            'status'              => 'cancelled',
            'cancellation_reason' => 'طلب العميل الإلغاء',
        ])->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('orders', [
            'id'     => $orderId,
            'status' => 'cancelled',
        ]);
    }

    public function test_order_creation_rejects_unlinked_customer(): void
    {
        [$company, , $product] = $this->createFixture();
        $unlinkedCustomer = $this->createCustomer('01070707070');

        Sanctum::actingAs($company);

        $this->postJson('/api/company/orders', [
            'customer_id'  => $unlinkedCustomer->id,
            'payment_type' => 'cash',
            'items'        => [
                ['company_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_id']);
    }

    /**
     * @return array{0: Company, 1: Customer, 2: CompanyProduct}
     */
    private function createFixture(): array
    {
        $governorate = Governorate::create([
            'name_ar' => 'الجيزة',
            'name_en' => 'Giza',
        ]);

        $company = Company::create([
            'name'           => 'شركة الاختبار',
            'tax_number'     => '444555666',
            'password'       => 'secret1234',
            'governorate_id' => $governorate->id,
            'wallet_balance' => 1000,
            'is_active'      => true,
        ]);

        $customer = $this->createCustomer('01060606060');

        CustomerCompanyLink::create([
            'customer_id' => $customer->id,
            'company_id'  => $company->id,
            'status'      => 'active',
            'linked_at'   => now(),
        ]);

        $product = CompanyProduct::create([
            'company_id' => $company->id,
            'name'       => 'منتج تجريبي',
            'cash_price' => 1000,
            'is_active'  => true,
        ]);

        return [$company, $customer, $product];
    }

    private function createCustomer(string $phone): Customer
    {
        $customer = Customer::create([
            'phone'     => $phone,
            'password'  => 'secret1234',
            'is_active' => true,
        ]);

        CustomerProfile::create([
            'customer_id' => $customer->id,
            'full_name'   => 'عميل',
        ]);

        return $customer;
    }

    private function createSuperAdmin(): SuperAdmin
    {
        return SuperAdmin::create([
            'name'     => 'Admin',
            'email'    => 'admin@watfil.test',
            'password' => 'secret1234',
        ]);
    }
}
