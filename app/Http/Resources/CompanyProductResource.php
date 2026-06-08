<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'image'       => $this->image ? asset('storage/' . $this->image) : null,
            'price'       => $this->price,
            'is_active'   => $this->is_active,
            'company'     => new CompanyResource($this->whenLoaded('company')),
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
