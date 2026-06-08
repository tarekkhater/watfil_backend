<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'name'           => 'sometimes|string|max:255',
            'tax_number'     => "sometimes|string|max:50|unique:companies,tax_number,{$companyId}",
            'password'       => 'sometimes|string|min:8',
            'governorate_id' => 'sometimes|integer|exists:governorates,id',
            'logo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'      => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'tax_number.unique'     => 'الرقم الضريبي مستخدم بالفعل',
            'password.min'          => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'governorate_id.exists' => 'المحافظة غير موجودة',
        ];
    }
}
