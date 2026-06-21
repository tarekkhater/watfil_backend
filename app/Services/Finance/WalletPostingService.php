<?php

namespace App\Services\Finance;

use App\Models\Company;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletPostingService
{
    /**
     * @param  array<string, string>  $meta
     */
    public function post(
        Company $company,
        string $direction,
        float $amount,
        string $category,
        ?string $description = null,
        ?string $idempotencyKey = null,
        ?Model $source = null,
        ?Model $actor = null,
        array $meta = []
    ): WalletTransaction {
        if (! in_array($direction, ['credit', 'debit'], true)) {
            throw ValidationException::withMessages([
                'direction' => ['اتجاه الحركة غير صحيح.'],
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['المبلغ يجب أن يكون أكبر من صفر.'],
            ]);
        }

        if ($idempotencyKey) {
            $existing = WalletTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing->load('meta');
            }
        }

        return DB::transaction(function () use (
            $company,
            $direction,
            $amount,
            $category,
            $description,
            $idempotencyKey,
            $source,
            $actor,
            $meta
        ): WalletTransaction {
            /** @var Company $lockedCompany */
            $lockedCompany = Company::query()
                ->whereKey($company->id)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = (float) $lockedCompany->wallet_balance;
            $balanceAfter = $direction === 'credit'
                ? $balanceBefore + $amount
                : $balanceBefore - $amount;

            if ($direction === 'debit' && $balanceAfter < 0) {
                throw ValidationException::withMessages([
                    'amount' => ['رصيد المحفظة غير كافٍ لهذه العملية.'],
                ]);
            }

            $lockedCompany->wallet_balance = $balanceAfter;
            $lockedCompany->save();

            $transaction = WalletTransaction::create([
                'company_id'       => $lockedCompany->id,
                'direction'        => $direction,
                'category'         => $category,
                'amount'           => round($amount, 2),
                'balance_before'   => round($balanceBefore, 2),
                'balance_after'    => round($balanceAfter, 2),
                'source_type'      => $source?->getMorphClass(),
                'source_id'        => $source?->getKey(),
                'performed_by_type'=> $actor?->getMorphClass(),
                'performed_by_id'  => $actor?->getKey(),
                'idempotency_key'  => $idempotencyKey,
                'description'      => $description,
            ]);

            if ($meta !== []) {
                $payload = [];
                foreach ($meta as $key => $value) {
                    $payload[] = [
                        'meta_key'   => $key,
                        'meta_value' => $value,
                    ];
                }

                $transaction->meta()->createMany($payload);
            }

            return $transaction->load('meta');
        });
    }
}
