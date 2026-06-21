<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id'  => 'required|integer|exists:companies,id',
            'description' => 'required|string|max:2000',
            'address'     => 'nullable|string|max:1000',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required'  => 'الشركة مطلوبة',
            'company_id.exists'    => 'الشركة غير موجودة',
            'description.required' => 'وصف المشكلة مطلوب',
            'image.image'          => 'الملف يجب أن يكون صورة',
            'image.max'            => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
        ];
    }
}
