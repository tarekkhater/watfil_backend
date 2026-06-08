<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'price'       => 'sometimes|numeric|min:0',
            'supplier_id' => 'sometimes|integer|exists:suppliers,id',
            'is_active'   => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'price.numeric'      => 'السعر يجب أن يكون رقمًا',
            'supplier_id.exists' => 'المورد غير موجود',
        ];
    }
}
