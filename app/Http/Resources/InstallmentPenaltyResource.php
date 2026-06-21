<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentPenaltyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'amount'                   => (float) $this->amount,
            'reason'                   => $this->reason,
            'applied_at'               => $this->applied_at?->toDateTimeString(),
        ];
    }
}
