<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'price'       => 'required|numeric|min:0',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'is_active'   => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'اسم المنتج مطلوب',
            'price.required'       => 'سعر المنتج مطلوب',
            'price.numeric'        => 'السعر يجب أن يكون رقمًا',
            'supplier_id.required' => 'المورد مطلوب',
            'supplier_id.exists'   => 'المورد غير موجود',
        ];
    }
}
