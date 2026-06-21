<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'amount'                   => (float) $this->amount,
            'payment_method'           => $this->payment_method,
            'notes'                    => $this->notes,
            'paid_at'                  => $this->paid_at?->toDateTimeString(),
            'installment_schedule_id'  => $this->installment_schedule_id,
            'created_at'               => $this->created_at?->toDateTimeString(),
        ];
    }
}
