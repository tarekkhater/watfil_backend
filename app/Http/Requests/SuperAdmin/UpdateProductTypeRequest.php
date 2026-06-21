<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productTypeId = $this->route('productType')?->id ?? $this->route('productType');

        return [
            'name'    => [
                'sometimes',
                'string',
                'max:45',
                Rule::unique('product_types', 'name')->ignore($productTypeId),
            ],
            'name_ar' => 'sometimes|string|max:45',
        ];
    }
}
