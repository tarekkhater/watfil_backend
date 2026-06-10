<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyProductInstallmentPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'months'             => $this->months,
            'down_payment'       => $this->down_payment,
            'installment_amount' => $this->installment_amount,
        ];
    }
}
