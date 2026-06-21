<?php

namespace App\Http\Resources;

use App\Support\MaintenanceLookups;
use App\Support\PublicFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'status' => $this->status,
            'customer' => [
                'full_name'       => $this->full_name,
                'phone'           => $this->phone,
                'governorate_id'  => $this->governorate_id,
                'governorate'     => $this->whenLoaded('governorate', fn () => [
                    'id'      => $this->governorate->id,
                    'name_ar' => $this->governorate->name_ar,
                    'name_en' => $this->governorate->name_en,
                ]),
                'city'            => $this->city,
                'area'            => $this->area,
                'address_details' => $this->address_details,
            ],
            'device' => [
                'details'                  => $this->device_details,
                'purification_system'      => $this->purification_system,
                'purification_system_label'=> MaintenanceLookups::label('purification_systems', $this->purification_system),
                'stages_count'             => $this->stages_count,
                'last_stage_change_dates'  => $this->last_stage_change_dates,
            ],
            'request' => [
                'primary_problem_type'       => $this->primary_problem_type,
                'primary_problem_type_label' => MaintenanceLookups::label('primary_problem_types', $this->primary_problem_type),
                'malfunction_type'           => $this->malfunction_type,
                'malfunction_type_label'     => MaintenanceLookups::label('malfunction_types', $this->malfunction_type),
                'notes'                      => $this->notes,
            ],
            'image'      => PublicFile::url($this->image),
            'company'    => new PublicCompanyResource($this->whenLoaded('company')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
