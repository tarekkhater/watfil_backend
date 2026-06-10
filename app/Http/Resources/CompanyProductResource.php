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
            'id'                => $this->id,
            'name'              => $this->name,
            'description'       => $this->description,
            'image'             => PublicFile::url($this->image),
            'cash_price'        => $this->cash_price,
            'is_active'         => $this->is_active,
            'installment_plans' => CompanyProductInstallmentPlanResource::collection(
                $this->whenLoaded('installmentPlans')
            ),
            'company'           => new CompanyResource($this->whenLoaded('company')),
            'created_at'        => $this->created_at?->toDateTimeString(),
        ];
    }
}
