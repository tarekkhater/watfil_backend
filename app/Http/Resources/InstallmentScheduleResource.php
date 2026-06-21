<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'installment_number'  => $this->installment_number,
            'type'                => $this->type,
            'due_date'            => $this->due_date?->toDateString(),
            'amount'              => (float) $this->amount,
            'paid_amount'         => (float) $this->paid_amount,
            'penalty_total'       => $this->whenLoaded('penalties', fn () => (float) $this->penalties->sum('amount'), fn () => (float) $this->penaltyTotal()),
            'amount_due'          => (float) $this->amountDue(),
            'status'              => $this->status,
            'paid_at'             => $this->paid_at?->toDateTimeString(),
            'penalties'           => InstallmentPenaltyResource::collection($this->whenLoaded('penalties')),
        ];
    }
}
