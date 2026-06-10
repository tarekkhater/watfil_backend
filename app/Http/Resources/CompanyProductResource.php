<?php

namespace App\Http\Resources;

use App\Support\PublicFile;
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
            'image'       => PublicFile::url($this->image),
            'price'       => $this->price,
            'is_active'   => $this->is_active,
            'company'     => new CompanyResource($this->whenLoaded('company')),
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
