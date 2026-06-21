<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicStoreProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->resource['id'],
            'source'            => $this->resource['source'],
            'name'              => $this->resource['name'],
            'description'       => $this->resource['description'],
            'image'             => $this->resource['image'],
            'cash_price'        => $this->resource['cash_price'],
            'installment_plans' => $this->resource['installment_plans'] ?? [],
            'supplier'          => $this->resource['supplier'] ?? null,
            'created_at'        => $this->resource['created_at'],
        ];
    }
}
