<?php

namespace Tests\Unit;

use App\Models\CommissionRule;
use App\Models\Company;
use App\Models\Governorate;
use App\Services\Finance\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_is_applied_once_with_idempotency(): void
    {
        $governorate = Governorate::create([
            'name_ar' => 'القاهرة',
            'name_en' => 'Cairo',
        ]);

        $company = Company::create([
            'name' => 'Test Company',
            'tax_number' => '123456789',
            'password' => 'secret1234',
            'governorate_id' => $governorate->id,
            'wallet_balance' => 1000,
            'is_active' => true,
        ]);

        CommissionRule::create([
            'name' => 'Default order commission',
            'trigger' => 'order_completed',
            'calculation_type' => 'percentage',
            'amount' => 10,
            'priority' => 1,
            'is_active' => true,
        ]);

        $service = $this->app->make(CommissionService::class);

        $service->apply(
            company: $company,
            trigger: 'order_completed',
            grossAmount: 200,
            source: $company,
            idempotencyKey: 'commission-event-1',
            actor: $company
        );

        $service->apply(
            company: $company,
            trigger: 'order_completed',
            grossAmount: 200,
            source: $company,
            idempotencyKey: 'commission-event-1',
            actor: $company
        );

        $company->refresh();

        $this->assertSame(980.0, (float) $company->wallet_balance);
        $this->assertDatabaseCount('commission_events', 1);
        $this->assertDatabaseHas('commission_events', [
            'company_id' => $company->id,
            'commission_amount' => 20,
            'idempotency_key' => 'commission-event-1',
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'company_id' => $company->id,
            'category' => 'commission',
            'amount' => 20,
        ]);
    }

    public function test_commission_is_skipped_for_exempt_source_channel(): void
    {
        $governorate = Governorate::create([
            'name_ar' => 'الجيزة',
            'name_en' => 'Giza',
        ]);

        $company = Company::create([
            'name' => 'Exempt Source Company',
            'tax_number' => '987654321',
            'password' => 'secret1234',
            'governorate_id' => $governorate->id,
            'wallet_balance' => 500,
            'is_active' => true,
        ]);

        CommissionRule::create([
            'name' => 'Order commission with referral exemption',
            'trigger' => 'order_completed',
            'calculation_type' => 'percentage',
            'amount' => 10,
            'priority' => 1,
            'is_active' => true,
            'metadata' => [
                'exempt_sources' => ['referral', 'internal'],
            ],
        ]);

        $service = $this->app->make(CommissionService::class);

        $event = $service->apply(
            company: $company,
            trigger: 'order_completed',
            grossAmount: 300,
            source: $company,
            idempotencyKey: 'commission-referral-exempt',
            actor: $company,
            sourceChannel: 'referral'
        );

        $company->refresh();

        $this->assertNull($event);
        $this->assertSame(500.0, (float) $company->wallet_balance);
        $this->assertDatabaseCount('commission_events', 0);
    }

    public function test_commission_applies_for_non_exempt_source_channel(): void
    {
        $governorate = Governorate::create([
            'name_ar' => 'الإسكندرية',
            'name_en' => 'Alexandria',
        ]);

        $company = Company::create([
            'name' => 'Store Source Company',
            'tax_number' => '555666777',
            'password' => 'secret1234',
            'governorate_id' => $governorate->id,
            'wallet_balance' => 500,
            'is_active' => true,
        ]);

        CommissionRule::create([
            'name' => 'Order commission with referral exemption',
            'trigger' => 'order_completed',
            'calculation_type' => 'fixed',
            'amount' => 25,
            'priority' => 1,
            'is_active' => true,
            'metadata' => [
                'exempt_sources' => ['referral'],
            ],
        ]);

        $service = $this->app->make(CommissionService::class);

        $service->apply(
            company: $company,
            trigger: 'order_completed',
            grossAmount: 300,
            source: $company,
            idempotencyKey: 'commission-store-source',
            actor: $company,
            sourceChannel: 'store'
        );

        $company->refresh();

        $this->assertSame(475.0, (float) $company->wallet_balance);
        $this->assertDatabaseHas('commission_events', [
            'company_id' => $company->id,
            'commission_amount' => 25,
        ]);
    }
}
