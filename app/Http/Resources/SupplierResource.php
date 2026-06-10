<?php

namespace App\Http\Resources;

use App\Support\PublicFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'logo'           => PublicFile::url($this->logo),
            'products_count' => $this->whenCounted('products'),
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
