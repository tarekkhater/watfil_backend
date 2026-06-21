<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('parent_category_id') && (int) $this->parent_category_id === 0) {
            $this->merge(['parent_category_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'name'               => 'sometimes|string|max:400',
            'parent_category_id' => 'nullable|integer|exists:categories,id',
            'product_type_id'    => 'nullable|integer|exists:product_types,id',
            'number_of_stages'   => 'nullable|integer|min:0|max:20',
        ];
    }
}
