<?php

namespace App\Services\Finance;

use App\Models\CommissionEvent;
use App\Models\CommissionRule;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    public function __construct(
        private readonly WalletPostingService $walletPostingService
    ) {
    }

    public function apply(
        Company $company,
        string $trigger,
        float $grossAmount,
        ?Model $source = null,
        ?string $idempotencyKey = null,
        ?Model $actor = null,
        ?string $sourceChannel = null
    ): ?CommissionEvent {
        if ($idempotencyKey) {
            $existing = CommissionEvent::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $rule = $this->resolveRule($trigger);
        if (! $rule || $this->isExempt($rule, $company, $sourceChannel)) {
            return null;
        }

        $commissionAmount = $this->calculateCommissionAmount($rule, $grossAmount);
        if ($commissionAmount <= 0) {
            return null;
        }

        return DB::transaction(function () use (
            $company,
            $rule,
            $source,
            $actor,
            $idempotencyKey,
            $grossAmount,
            $commissionAmount
        ): CommissionEvent {
            $event = CommissionEvent::create([
                'company_id'         => $company->id,
                'commission_rule_id' => $rule->id,
                'source_type'        => $source?->getMorphClass(),
                'source_id'          => $source?->getKey(),
                'gross_amount'       => round($grossAmount, 2),
                'commission_amount'  => round($commissionAmount, 2),
                'net_amount'         => round(max($grossAmount - $commissionAmount, 0), 2),
                'currency'           => 'EGP',
                'status'             => 'pending',
                'idempotency_key'    => $idempotencyKey,
            ]);

            $transaction = $this->walletPostingService->post(
                company: $company,
                direction: 'debit',
                amount: $commissionAmount,
                category: 'commission',
                description: sprintf('عمولة %s (%s)', $rule->name, $rule->trigger),
                idempotencyKey: $idempotencyKey ? $idempotencyKey . ':wallet' : null,
                source: $source,
                actor: $actor,
                meta: [
                    'trigger' => $rule->trigger,
                    'rule_id' => (string) $rule->id,
                ]
            );

            $event->update([
                'wallet_transaction_id' => $transaction->id,
                'status'                => 'posted',
                'processed_at'          => now(),
            ]);

            return $event->fresh(['rule', 'walletTransaction']);
        });
    }

    private function resolveRule(string $trigger): ?CommissionRule
    {
        $now = Carbon::now();

        return CommissionRule::query()
            ->where('trigger', $trigger)
            ->where('is_active', true)
            ->where(function ($query) use ($now): void {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('priority')
            ->orderByDesc('id')
            ->first();
    }

    private function calculateCommissionAmount(CommissionRule $rule, float $grossAmount): float
    {
        $amount = (float) $rule->amount;

        if ($rule->calculation_type === 'percentage') {
            return round(($grossAmount * $amount) / 100, 2);
        }

        return round($amount, 2);
    }

    private function isExempt(CommissionRule $rule, Company $company, ?string $sourceChannel): bool
    {
        $metadata = $rule->metadata ?? [];

        if (in_array($company->id, $metadata['exempt_company_ids'] ?? [], true)) {
            return true;
        }

        if ($sourceChannel === null) {
            return false;
        }

        if (in_array($sourceChannel, $metadata['exempt_sources'] ?? [], true)) {
            return true;
        }

        $applyOnly = $metadata['apply_only_sources'] ?? null;
        if (is_array($applyOnly) && $applyOnly !== [] && ! in_array($sourceChannel, $applyOnly, true)) {
            return true;
        }

        return false;
    }
}
