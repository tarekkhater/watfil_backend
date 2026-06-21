<?php

namespace Tests\Feature;

use App\Models\CommissionRule;
use App\Models\Company;
use App\Models\CompanyProduct;
use App\Models\CompanyProductInstallmentPlan;
use App\Models\Customer;
use App\Models\CustomerCompanyLink;
use App\Models\CustomerProfile;
use App\Models\Governorate;
use App\Models\InstallmentContract;
use App\Models\InstallmentSchedule;
use App\Models\SuperAdmin;
use App\Services\Installment\InstallmentContractService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstallmentContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_installment_order_creates_contract_and_schedule(): void
    {
        [$company, $customer, $product, $plan] = $this->createInstallmentFixture();

        CommissionRule::create([
            'name'             => 'Default order commission',
            'trigger'          => 'order_completed',
            'calculation_type' => 'percentage',
            'amount'           => 5,
            'priority'         => 1,
            'is_active'        => true,
        ]);

        Sanctum::actingAs($company);

        $orderId = $this->postJson('/api/company/orders', [
            'customer_id'         => $customer->id,
            'payment_type'        => 'installment',
            'installment_plan_id' => $plan->id,
            'items'               => [
                ['company_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.payment_type', 'installment')
            ->assertJsonPath('data.total_amount', 4100)
            ->json('data.id');

        $this->patchJson("/api/company/orders/{$orderId}/status", [
            'status' => 'processing',
        ])->assertOk();

        $this->patchJson("/api/company/orders/{$orderId}/status", [
            'status' => 'completed',
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $contract = InstallmentContract::query()->where('order_id', $orderId)->first();

        $this->assertNotNull($contract);
        $this->assertSame('active', $contract->status);
        $this->assertSame(4100.0, (float) $contract->principal_amount);
        $this->assertSame(13, $contract->schedule()->count());

        $this->getJson('/api/company/installment-contracts')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        Sanctum::actingAs($customer);

        $this->getJson("/api/customer/installment-contracts/{$contract->id}")
            ->assertOk()
            ->assertJsonPath('data.schedule.0.type', 'down_payment');
    }

    public function test_company_can_record_installment_payment_and_credit_wallet(): void
    {
        [$company, $customer, $product, $plan] = $this->createInstallmentFixture();
        $contract = $this->createCompletedInstallmentContract($company, $customer, $product, $plan);

        $downPaymentSchedule = InstallmentSchedule::query()
            ->where('installment_contract_id', $contract->id)
            ->where('installment_number', 0)
            ->firstOrFail();

        Sanctum::actingAs($company);

        $this->postJson("/api/company/installment-contracts/{$contract->id}/payments", [
            'installment_schedule_id' => $downPaymentSchedule->id,
            'amount'                  => 500,
            'payment_method'          => 'cash',
        ])->assertCreated()
            ->assertJsonPath('data.amount', 500);

        $company->refresh();
        $this->assertSame(500.0, (float) $company->wallet_balance);

        $downPaymentSchedule->refresh();
        $this->assertSame('paid', $downPaymentSchedule->status);

        $contract->refresh();
        $this->assertSame(500.0, (float) $contract->paid_amount);
        $this->assertSame(3600.0, (float) $contract->outstanding_amount);
    }

    public function test_partial_payment_marks_schedule_as_partial(): void
    {
        [$company, $customer, $product, $plan] = $this->createInstallmentFixture();
        $contract = $this->createCompletedInstallmentContract($company, $customer, $product, $plan);

        $schedule = InstallmentSchedule::query()
            ->where('installment_contract_id', $contract->id)
            ->where('installment_number', 1)
            ->firstOrFail();

        Sanctum::actingAs($company);

        $this->postJson("/api/company/installment-contracts/{$contract->id}/payments", [
            'installment_schedule_id' => $schedule->id,
            'amount'                  => 100,
        ])->assertCreated();

        $schedule->refresh();
        $this->assertSame('partial', $schedule->status);
        $this->assertSame(100.0, (float) $schedule->paid_amount);
    }

    public function test_process_overdue_marks_schedule_and_applies_penalty(): void
    {
        [$company, $customer, $product, $plan] = $this->createInstallmentFixture();
        $contract = $this->createCompletedInstallmentContract($company, $customer, $product, $plan);

        $schedule = InstallmentSchedule::query()
            ->where('installment_contract_id', $contract->id)
            ->where('installment_number', 1)
            ->firstOrFail();

        $schedule->update([
            'due_date' => Carbon::now()->subDays(10)->toDateString(),
        ]);

        $processed = $this->app->make(InstallmentContractService::class)->processOverdue();

        $this->assertGreaterThanOrEqual(1, $processed);

        $schedule->refresh();
        $this->assertSame('overdue', $schedule->status);
        $this->assertDatabaseHas('installment_penalties', [
            'installment_schedule_id' => $schedule->id,
            'reason'                  => 'late_payment',
        ]);
    }

    public function test_installment_order_requires_plan_id(): void
    {
        [$company, $customer, $product] = $this->createInstallmentFixture();

        Sanctum::actingAs($company);

        $this->postJson('/api/company/orders', [
            'customer_id'  => $customer->id,
            'payment_type' => 'installment',
            'items'        => [
                ['company_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['installment_plan_id']);
    }

    public function test_super_admin_can_view_overdue_summary(): void
    {
        $admin = SuperAdmin::create([
            'name'     => 'Admin',
            'email'    => 'admin@watfil.test',
            'password' => 'secret1234',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/super-admin/installment-contracts/overdue-summary')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['overdue_schedules', 'defaulted_contracts'],
            ]);
    }

    /**
     * @return array{0: Company, 1: Customer, 2: CompanyProduct, 3: CompanyProductInstallmentPlan}
     */
    private function createInstallmentFixture(): array
    {
        $governorate = Governorate::create([
            'name_ar' => 'الجيزة',
            'name_en' => 'Giza',
        ]);

        $company = Company::create([
            'name'           => 'شركة التقسيط',
            'tax_number'     => '777888999',
            'password'       => 'secret1234',
            'governorate_id' => $governorate->id,
            'wallet_balance' => 0,
            'is_active'      => true,
        ]);

        $customer = Customer::create([
            'phone'     => '01090909090',
            'password'  => 'secret1234',
            'is_active' => true,
        ]);

        CustomerProfile::create([
            'customer_id' => $customer->id,
            'full_name'   => 'عميل تقسيط',
        ]);

        CustomerCompanyLink::create([
            'customer_id' => $customer->id,
            'company_id'  => $company->id,
            'status'      => 'active',
            'linked_at'   => now(),
        ]);

        $product = CompanyProduct::create([
            'company_id' => $company->id,
            'name'       => 'فلتر تقسيط',
            'cash_price' => 5000,
            'is_active'  => true,
        ]);

        $plan = CompanyProductInstallmentPlan::create([
            'company_product_id' => $product->id,
            'months'             => 12,
            'down_payment'       => 500,
            'installment_amount' => 300,
        ]);

        return [$company, $customer, $product, $plan];
    }

    private function createCompletedInstallmentContract(
        Company $company,
        Customer $customer,
        CompanyProduct $product,
        CompanyProductInstallmentPlan $plan
    ): InstallmentContract {
        CommissionRule::create([
            'name'             => 'Default order commission',
            'trigger'          => 'order_completed',
            'calculation_type' => 'percentage',
            'amount'           => 0,
            'priority'         => 1,
            'is_active'        => true,
        ]);

        Sanctum::actingAs($company);

        $orderId = $this->postJson('/api/company/orders', [
            'customer_id'         => $customer->id,
            'payment_type'        => 'installment',
            'installment_plan_id' => $plan->id,
            'items'               => [
                ['company_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->json('data.id');

        $this->patchJson("/api/company/orders/{$orderId}/status", ['status' => 'processing']);
        $this->patchJson("/api/company/orders/{$orderId}/status", ['status' => 'completed']);

        return InstallmentContract::query()->where('order_id', $orderId)->firstOrFail();
    }
}
