<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'company_id'             => $this->company_id,
            'amount'                 => $this->amount,
            'status'                 => $this->status,
            'approved_at'            => $this->approved_at?->toDateTimeString(),
            'rejected_at'            => $this->rejected_at?->toDateTimeString(),
            'rejection_reason'       => $this->rejection_reason,
            'paid_at'                => $this->paid_at?->toDateTimeString(),
            'payout_reference'       => $this->payout_reference,
            'created_at'             => $this->created_at?->toDateTimeString(),
            'reserved_transaction'   => new WalletTransactionResource($this->whenLoaded('reservedTransaction')),
            'release_transaction'    => new WalletTransactionResource($this->whenLoaded('releaseTransaction')),
            'audit_log'              => $this->whenLoaded('audits', function (): array {
                return $this->audits->map(function ($audit): array {
                    return [
                        'id'         => $audit->id,
                        'action'     => $audit->action,
                        'note'       => $audit->note,
                        'metadata'   => $audit->metadata,
                        'created_at' => $audit->created_at?->toDateTimeString(),
                    ];
                })->all();
            }, []),
        ];
    }
}
