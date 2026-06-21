<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'full_name'      => $this->full_name,
            'governorate'    => new GovernorateResource($this->whenLoaded('governorate')),
            'governorate_id' => $this->governorate_id,
            'city'           => $this->city,
            'address'        => $this->address,
            'avatar'         => $this->avatar,
            'date_of_birth'  => $this->date_of_birth?->toDateString(),
            'gender'         => $this->gender,
            'risk_flag'      => $this->risk_flag,
        ];
    }
}
