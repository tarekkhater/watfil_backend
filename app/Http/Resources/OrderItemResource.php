<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'company_product_id' => $this->company_product_id,
            'product_name'       => $this->product_name,
            'quantity'           => $this->quantity,
            'unit_price'         => (float) $this->unit_price,
            'line_total'         => (float) $this->line_total,
            'metadata'           => $this->metadata,
        ];
    }
}
