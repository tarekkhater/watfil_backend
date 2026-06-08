<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'tax_number'  => $this->tax_number,
            'is_active'   => $this->is_active,
            'logo'        => $this->logo ? asset('storage/' . $this->logo) : null,
            'governorate' => new GovernorateResource($this->whenLoaded('governorate')),
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
