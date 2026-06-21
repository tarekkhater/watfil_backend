<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentPlanOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'months'             => $this->resource['months'],
            'down_payment'       => $this->resource['down_payment'],
            'installment_amount' => $this->resource['installment_amount'],
            'remaining_amount'   => $this->resource['remaining_amount'],
            'total_amount'       => $this->resource['total_amount'],
        ];
    }
}
