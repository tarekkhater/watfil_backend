<?php

namespace App\Services\Finance;

use App\Models\Company;
use App\Models\SuperAdmin;
use App\Models\WithdrawalAudit;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawalService
{
    public function __construct(
        private readonly WalletPostingService $walletPostingService
    ) {
    }

    public function createRequest(
        Company $company,
        float $amount,
        ?string $idempotencyKey = null
    ): WithdrawalRequest {
        $minimum = (float) config('finance.min_withdrawal_amount', 100);
        if ($amount < $minimum) {
            throw ValidationException::withMessages([
                'amount' => ["الحد الأدنى لطلب السحب هو {$minimum}."],
            ]);
        }

        if ($idempotencyKey) {
            $existing = WithdrawalRequest::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing->load(['reservedTransaction', 'audits']);
            }
        }

        return DB::transaction(function () use ($company, $amount, $idempotencyKey): WithdrawalRequest {
            $reservationTransaction = $this->walletPostingService->post(
                company: $company,
                direction: 'debit',
                amount: $amount,
                category: 'withdrawal_hold',
                description: 'حجز رصيد لطلب سحب',
                idempotencyKey: $idempotencyKey ? $idempotencyKey . ':hold' : null,
                source: $company,
                actor: $company
            );

            $request = WithdrawalRequest::create([
                'company_id'              => $company->id,
                'amount'                  => round($amount, 2),
                'status'                  => 'pending',
                'reserved_transaction_id' => $reservationTransaction->id,
                'idempotency_key'         => $idempotencyKey,
            ]);

            $this->addAudit($request, 'created', $company, 'تم إنشاء طلب السحب');

            return $request->load(['reservedTransaction', 'audits']);
        });
    }

    public function approve(
        WithdrawalRequest $withdrawalRequest,
        SuperAdmin $admin,
        ?string $note = null
    ): WithdrawalRequest {
        if ($withdrawalRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['لا يمكن اعتماد الطلب في حالته الحالية.'],
            ]);
        }

        $withdrawalRequest->update([
            'status'      => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $this->addAudit($withdrawalRequest, 'approved', $admin, $note);

        return $withdrawalRequest->fresh(['company', 'audits', 'reservedTransaction']);
    }

    public function reject(
        WithdrawalRequest $withdrawalRequest,
        SuperAdmin $admin,
        string $reason,
        ?string $note = null
    ): WithdrawalRequest {
        if (! in_array($withdrawalRequest->status, ['pending', 'approved'], true)) {
            throw ValidationException::withMessages([
                'status' => ['لا يمكن رفض الطلب في حالته الحالية.'],
            ]);
        }

        return DB::transaction(function () use ($withdrawalRequest, $admin, $reason, $note): WithdrawalRequest {
            $company = $withdrawalRequest->company;

            $releaseTransaction = $this->walletPostingService->post(
                company: $company,
                direction: 'credit',
                amount: (float) $withdrawalRequest->amount,
                category: 'withdrawal_release',
                description: 'إرجاع رصيد طلب سحب مرفوض',
                idempotencyKey: 'withdrawal-reject:' . $withdrawalRequest->id,
                source: $withdrawalRequest,
                actor: $admin
            );

            $withdrawalRequest->update([
                'status'                 => 'rejected',
                'rejected_at'            => now(),
                'rejection_reason'       => $reason,
                'release_transaction_id' => $releaseTransaction->id,
            ]);

            $this->addAudit($withdrawalRequest, 'rejected', $admin, $note, [
                'reason' => $reason,
            ]);

            return $withdrawalRequest->fresh(['company', 'audits', 'reservedTransaction', 'releaseTransaction']);
        });
    }

    public function markAsPaid(
        WithdrawalRequest $withdrawalRequest,
        SuperAdmin $admin,
        string $payoutReference,
        ?string $note = null
    ): WithdrawalRequest {
        if ($withdrawalRequest->status !== 'approved') {
            throw ValidationException::withMessages([
                'status' => ['يجب اعتماد الطلب أولاً قبل تحويله إلى مدفوع.'],
            ]);
        }

        $withdrawalRequest->update([
            'status'           => 'paid',
            'paid_at'          => now(),
            'payout_reference' => $payoutReference,
        ]);

        $this->addAudit($withdrawalRequest, 'paid', $admin, $note, [
            'payout_reference' => $payoutReference,
        ]);

        return $withdrawalRequest->fresh(['company', 'audits', 'reservedTransaction']);
    }

    /**
     * @param array<string, string> $metadata
     */
    private function addAudit(
        WithdrawalRequest $withdrawalRequest,
        string $action,
        Company|SuperAdmin $actor,
        ?string $note = null,
        array $metadata = []
    ): void {
        WithdrawalAudit::create([
            'withdrawal_request_id' => $withdrawalRequest->id,
            'action'                => $action,
            'actor_type'            => $actor->getMorphClass(),
            'actor_id'              => $actor->getKey(),
            'note'                  => $note,
            'metadata'              => $metadata === [] ? null : $metadata,
        ]);
    }
}
