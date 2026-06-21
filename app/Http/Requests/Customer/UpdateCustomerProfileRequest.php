<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->user()?->id;

        return [
            'email'          => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customerId),
            ],
            'full_name'      => 'sometimes|string|max:255',
            'governorate_id' => 'sometimes|nullable|integer|exists:governorates,id',
            'city'           => 'sometimes|nullable|string|max:255',
            'address'        => 'sometimes|nullable|string|max:1000',
            'date_of_birth'  => 'sometimes|nullable|date|before:today',
            'gender'         => 'sometimes|nullable|in:male,female',
        ];
    }

    public function messages(): array
    {
        return [
            'email.email'         => 'البريد الإلكتروني غير صالح',
            'email.unique'        => 'البريد الإلكتروني مستخدم بالفعل',
            'governorate_id.exists' => 'المحافظة غير موجودة',
            'date_of_birth.before' => 'تاريخ الميلاد يجب أن يكون في الماضي',
            'gender.in'           => 'الجنس غير صالح',
        ];
    }
}
