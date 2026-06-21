<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderSourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'channel'        => $this->channel,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'metadata'       => $this->metadata,
        ];
    }
}
