<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Governorate;
use App\Models\SuperAdmin;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinanceCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_adjustment_creates_ledger_entry_and_respects_idempotency(): void
    {
        $admin = SuperAdmin::create([
            'name' => 'Finance Admin',
            'email' => 'finance-admin@example.com',
            'password' => 'secret1234',
        ]);

        $company = $this->createCompany(100);

        Sanctum::actingAs($admin);

        $payload = [
            'amount' => 50,
            'type' => 'credit',
            'idempotency_key' => 'wallet-adjust-key',
            'reason' => 'manual topup',
        ];

        $this->postJson("/api/super-admin/companies/{$company->id}/wallet/adjust", $payload)
            ->assertOk();

        $this->postJson("/api/super-admin/companies/{$company->id}/wallet/adjust", $payload)
            ->assertOk();

        $company->refresh();

        $this->assertSame(150.0, (float) $company->wallet_balance);
        $this->assertDatabaseCount('wallet_transactions', 1);
        $this->assertDatabaseHas('wallet_transactions', [
            'company_id' => $company->id,
            'direction' => 'credit',
            'category' => 'manual_adjustment',
            'idempotency_key' => 'wallet-adjust-key',
        ]);
    }

    public function test_company_withdrawal_lifecycle_can_be_approved_and_marked_paid(): void
    {
        $admin = SuperAdmin::create([
            'name' => 'Finance Admin',
            'email' => 'finance-admin-2@example.com',
            'password' => 'secret1234',
        ]);

        $company = $this->createCompany(500);

        Sanctum::actingAs($company);

        $this->postJson('/api/company/wallet/withdrawals', [
            'amount' => 200,
            'idempotency_key' => 'withdrawal-1',
        ])->assertCreated();

        $company->refresh();
        $this->assertSame(300.0, (float) $company->wallet_balance);

        $withdrawalRequest = WithdrawalRequest::firstOrFail();
        $this->assertSame('pending', $withdrawalRequest->status);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/super-admin/finance/withdrawal-requests/{$withdrawalRequest->id}/approve", [])
            ->assertOk();

        $this->patchJson("/api/super-admin/finance/withdrawal-requests/{$withdrawalRequest->id}/pay", [
            'payout_reference' => 'BANK-REF-1001',
        ])->assertOk();

        $withdrawalRequest->refresh();
        $this->assertSame('paid', $withdrawalRequest->status);
        $this->assertNotNull($withdrawalRequest->paid_at);
    }

    public function test_rejecting_withdrawal_restores_company_balance(): void
    {
        $admin = SuperAdmin::create([
            'name' => 'Finance Admin',
            'email' => 'finance-admin-3@example.com',
            'password' => 'secret1234',
        ]);

        $company = $this->createCompany(400);

        Sanctum::actingAs($company);
        $this->postJson('/api/company/wallet/withdrawals', [
            'amount' => 150,
            'idempotency_key' => 'withdrawal-2',
        ])->assertCreated();

        $company->refresh();
        $this->assertSame(250.0, (float) $company->wallet_balance);

        $withdrawalRequest = WithdrawalRequest::firstOrFail();

        Sanctum::actingAs($admin);
        $this->patchJson("/api/super-admin/finance/withdrawal-requests/{$withdrawalRequest->id}/reject", [
            'reason' => 'KYC failed',
        ])->assertOk();

        $company->refresh();
        $withdrawalRequest->refresh();

        $this->assertSame('rejected', $withdrawalRequest->status);
        $this->assertSame(400.0, (float) $company->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'company_id' => $company->id,
            'category' => 'withdrawal_release',
            'direction' => 'credit',
        ]);
    }

    public function test_company_can_list_wallet_transactions(): void
    {
        $company = $this->createCompany(200);

        Sanctum::actingAs($company);

        WalletTransaction::create([
            'company_id' => $company->id,
            'direction' => 'credit',
            'category' => 'manual_adjustment',
            'amount' => 50,
            'balance_before' => 150,
            'balance_after' => 200,
        ]);

        $response = $this->getJson('/api/company/wallet/transactions?direction=credit');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.category', 'manual_adjustment')
            ->assertJsonPath('data.0.direction', 'credit');
    }

    public function test_admin_commission_summary_returns_totals(): void
    {
        $admin = SuperAdmin::create([
            'name' => 'Summary Admin',
            'email' => 'summary-admin@example.com',
            'password' => 'secret1234',
        ]);

        $company = $this->createCompany(1000);

        \App\Models\CommissionEvent::create([
            'company_id' => $company->id,
            'gross_amount' => 500,
            'commission_amount' => 50,
            'net_amount' => 450,
            'status' => 'posted',
            'processed_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/super-admin/finance/commissions/summary')
            ->assertOk()
            ->assertJsonPath('data.events_count', 1)
            ->assertJsonPath('data.commission_total', 50)
            ->assertJsonPath('data.gross_total', 500);
    }

    public function test_withdrawal_idempotency_prevents_duplicate_hold(): void
    {
        $company = $this->createCompany(600);

        Sanctum::actingAs($company);

        $payload = [
            'amount' => 200,
            'idempotency_key' => 'withdrawal-dup-key',
        ];

        $this->postJson('/api/company/wallet/withdrawals', $payload)->assertCreated();
        $this->postJson('/api/company/wallet/withdrawals', $payload)->assertCreated();

        $company->refresh();

        $this->assertSame(400.0, (float) $company->wallet_balance);
        $this->assertDatabaseCount('withdrawal_requests', 1);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    private function createCompany(float $walletBalance): Company
    {
        $governorate = Governorate::create([
            'name_ar' => 'القاهرة',
            'name_en' => 'Cairo',
        ]);

        return Company::create([
            'name' => 'Company ' . fake()->numberBetween(100, 999),
            'tax_number' => (string) fake()->numberBetween(100000, 999999),
            'password' => 'secret1234',
            'governorate_id' => $governorate->id,
            'wallet_balance' => $walletBalance,
            'is_active' => true,
        ]);
    }
}
