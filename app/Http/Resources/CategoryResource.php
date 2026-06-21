<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'parent_category_id' => $this->parent_category_id,
            'product_type_id'    => $this->product_type_id,
            'number_of_stages'   => $this->number_of_stages,
            'product_type'       => new ProductTypeResource($this->whenLoaded('productType')),
            'parent'             => new CategoryResource($this->whenLoaded('parent')),
            'children'           => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
