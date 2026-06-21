<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class VerifyRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'          => 'required|string|regex:/^01[0-9]{9}$/|unique:customers,phone',
            'otp'            => 'required|string|size:6',
            'name'           => 'required|string|max:255',
            'password'       => 'required|string|min:8|confirmed',
            'governorate_id' => 'required|integer|exists:governorates,id',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'          => 'رقم الهاتف مطلوب',
            'phone.regex'             => 'رقم الهاتف غير صالح',
            'phone.unique'            => 'رقم الهاتف مسجل بالفعل',
            'otp.required'            => 'رمز التحقق مطلوب',
            'otp.size'                => 'رمز التحقق يجب أن يكون 6 أرقام',
            'name.required'           => 'الاسم مطلوب',
            'password.required'       => 'كلمة المرور مطلوبة',
            'password.min'            => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed'      => 'تأكيد كلمة المرور غير متطابق',
            'governorate_id.required' => 'المحافظة مطلوبة',
            'governorate_id.exists'   => 'المحافظة غير موجودة',
        ];
    }
}
