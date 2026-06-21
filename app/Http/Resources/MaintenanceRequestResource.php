<?php

namespace App\Http\Resources;

use App\Support\PublicFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'description' => $this->description,
            'address'     => $this->address,
            'image'       => PublicFile::url($this->image),
            'status'      => $this->status,
            'company'     => new PublicCompanyResource($this->whenLoaded('company')),
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
