<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'tax_number'     => 'required|string|max:50|unique:companies,tax_number',
            'password'       => 'required|string|min:8',
            'governorate_id' => 'required|integer|exists:governorates,id',
            'logo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'      => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'اسم الشركة مطلوب',
            'tax_number.required'     => 'الرقم الضريبي مطلوب',
            'tax_number.unique'       => 'الرقم الضريبي مستخدم بالفعل',
            'password.required'       => 'كلمة المرور مطلوبة',
            'password.min'            => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'governorate_id.required' => 'المحافظة مطلوبة',
            'governorate_id.exists'   => 'المحافظة غير موجودة',
        ];
    }
}
