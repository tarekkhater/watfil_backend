<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerCompanyLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'status'     => $this->status,
            'linked_at'  => $this->linked_at?->toDateTimeString(),
            'customer'   => new CustomerResource($this->whenLoaded('customer')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
