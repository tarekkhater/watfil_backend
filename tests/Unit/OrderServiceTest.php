<?php

namespace Tests\Unit;

use App\Models\CommissionRule;
use App\Models\Company;
use App\Models\CompanyProduct;
use App\Models\Customer;
use App\Models\CustomerCompanyLink;
use App\Models\CustomerProfile;
use App\Models\Governorate;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_creation_calculates_totals_and_records_history(): void
    {
        [$company, $customer, $product] = $this->createOrderFixture();

        $service = $this->app->make(OrderService::class);

        $order = $service->create([
            'company_id'   => $company->id,
            'customer_id'  => $customer->id,
            'payment_type' => 'cash',
            'items'        => [
                ['company_product_id' => $product->id, 'quantity' => 2],
            ],
            'discount' => 50,
            'source'   => ['channel' => 'direct'],
        ], $company);

        $this->assertSame('pending', $order->status);
        $this->assertSame(2000.0, (float) $order->subtotal);
        $this->assertSame(50.0, (float) $order->discount);
        $this->assertSame(1950.0, (float) $order->total_amount);
        $this->assertDatabaseHas('order_items', [
            'order_id'     => $order->id,
            'product_name' => 'فلتر مياه',
            'quantity'     => 2,
            'line_total'   => 2000,
        ]);
        $this->assertDatabaseHas('order_sources', [
            'order_id' => $order->id,
            'channel'  => 'direct',
        ]);
        $this->assertDatabaseHas('order_status_history', [
            'order_id'    => $order->id,
            'from_status' => null,
            'to_status'   => 'pending',
        ]);
    }

    public function test_order_creation_is_idempotent(): void
    {
        [$company, $customer, $product] = $this->createOrderFixture();

        $service = $this->app->make(OrderService::class);
        $payload = [
            'company_id'   => $company->id,
            'customer_id'  => $customer->id,
            'payment_type' => 'cash',
            'items'        => [
                ['company_product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $service->create($payload, $company, 'order-create-1');
        $service->create($payload, $company, 'order-create-1');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_completing_order_applies_commission_once(): void
    {
        [$company, $customer, $product] = $this->createOrderFixture(1000);

        CommissionRule::create([
            'name'             => 'Order commission',
            'trigger'          => 'order_completed',
            'calculation_type' => 'percentage',
            'amount'           => 10,
            'priority'         => 1,
            'is_active'        => true,
        ]);

        $service = $this->app->make(OrderService::class);

        $order = $service->create([
            'company_id'   => $company->id,
            'customer_id'  => $customer->id,
            'payment_type' => 'cash',
            'items'        => [
                ['company_product_id' => $product->id, 'quantity' => 1],
            ],
            'source' => ['channel' => 'link'],
        ], $company);

        $service->transitionStatus($order, 'processing', $company);
        $service->transitionStatus($order->fresh(), 'completed', $company);

        $company->refresh();

        $this->assertSame(900.0, (float) $company->wallet_balance);
        $this->assertDatabaseCount('commission_events', 1);
        $this->assertDatabaseHas('commission_events', [
            'source_type'       => Order::class,
            'source_id'         => $order->id,
            'commission_amount' => 100,
        ]);
    }

    public function test_completing_referral_order_skips_commission(): void
    {
        [$company, $customer, $product] = $this->createOrderFixture(1000);

        CommissionRule::create([
            'name'             => 'Order commission with referral exemption',
            'trigger'          => 'order_completed',
            'calculation_type' => 'percentage',
            'amount'           => 10,
            'priority'         => 1,
            'is_active'        => true,
            'metadata'         => [
                'exempt_sources' => ['referral'],
            ],
        ]);

        $service = $this->app->make(OrderService::class);

        $order = $service->create([
            'company_id'   => $company->id,
            'customer_id'  => $customer->id,
            'payment_type' => 'cash',
            'items'        => [
                ['company_product_id' => $product->id, 'quantity' => 1],
            ],
            'source' => ['channel' => 'referral'],
        ], $company);

        $service->transitionStatus($order, 'processing', $company);
        $service->transitionStatus($order->fresh(), 'completed', $company);

        $company->refresh();

        $this->assertSame(1000.0, (float) $company->wallet_balance);
        $this->assertDatabaseCount('commission_events', 0);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        [$company, $customer, $product] = $this->createOrderFixture();

        $service = $this->app->make(OrderService::class);

        $order = $service->create([
            'company_id'   => $company->id,
            'customer_id'  => $customer->id,
            'payment_type' => 'cash',
            'items'        => [
                ['company_product_id' => $product->id, 'quantity' => 1],
            ],
        ], $company);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $service->transitionStatus($order, 'completed', $company);
    }

    public function test_installment_order_uses_selected_plan_totals(): void
    {
        [$company, $customer, $product] = $this->createOrderFixture();

        $product->installmentPlans()->create([
            'months'             => 6,
            'down_payment'       => 1000,
            'installment_amount' => 700,
        ]);

        $service = $this->app->make(OrderService::class);

        $order = $service->create([
            'company_id'       => $company->id,
            'customer_id'      => $customer->id,
            'payment_type'     => 'installment',
            'installment_plan' => [
                'months'             => 6,
                'down_payment'       => 1000,
                'installment_amount' => 700,
            ],
            'items' => [
                ['company_product_id' => $product->id, 'quantity' => 1],
            ],
        ], $customer);

        $this->assertSame('installment', $order->payment_type);
        $this->assertSame(5200.0, (float) $order->total_amount);
        $this->assertSame(6, $order->installment_plan['months']);
        $this->assertSame(4200.0, (float) $order->installment_plan['remaining_amount']);
    }

    /**
     * @return array{0: Company, 1: Customer, 2: CompanyProduct}
     */
    private function createOrderFixture(float $walletBalance = 5000): array
    {
        $governorate = Governorate::create([
            'name_ar' => 'القاهرة',
            'name_en' => 'Cairo',
        ]);

        $company = Company::create([
            'name'           => 'شركة الطلبات',
            'tax_number'     => '111222333',
            'password'       => 'secret1234',
            'governorate_id' => $governorate->id,
            'wallet_balance' => $walletBalance,
            'is_active'      => true,
        ]);

        $customer = Customer::create([
            'phone'     => '01090090090',
            'password'  => 'secret1234',
            'is_active' => true,
        ]);

        CustomerProfile::create([
            'customer_id' => $customer->id,
            'full_name'   => 'عميل الطلبات',
        ]);

        CustomerCompanyLink::create([
            'customer_id' => $customer->id,
            'company_id'  => $company->id,
            'status'      => 'active',
            'linked_at'   => now(),
        ]);

        $product = CompanyProduct::create([
            'company_id' => $company->id,
            'name'       => 'فلتر مياه',
            'cash_price' => 1000,
            'is_active'  => true,
        ]);

        return [$company, $customer, $product];
    }
}
