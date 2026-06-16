<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'          => 'required|string|max:20|unique:customers,phone',
            'email'          => 'nullable|email|max:255|unique:customers,email',
            'password'       => 'required|string|min:8|confirmed',
            'full_name'      => 'required|string|max:255',
            'governorate_id' => 'nullable|integer|exists:governorates,id',
            'city'           => 'nullable|string|max:255',
            'address'        => 'nullable|string|max:1000',
            'company_id'     => 'nullable|integer|exists:companies,id',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'      => 'رقم الهاتف مطلوب',
            'phone.unique'        => 'رقم الهاتف مستخدم بالفعل',
            'email.email'         => 'البريد الإلكتروني غير صالح',
            'email.unique'        => 'البريد الإلكتروني مستخدم بالفعل',
            'password.required'   => 'كلمة المرور مطلوبة',
            'password.min'        => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed'  => 'تأكيد كلمة المرور غير متطابق',
            'full_name.required'  => 'الاسم الكامل مطلوب',
            'governorate_id.exists' => 'المحافظة غير موجودة',
            'company_id.exists'   => 'الشركة غير موجودة',
        ];
    }
}
