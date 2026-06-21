<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'    => 'required|string|regex:/^01[0-9]{9}$/',
            'password' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'    => 'رقم الهاتف مطلوب',
            'phone.regex'       => 'رقم الهاتف غير صالح',
            'password.required' => 'كلمة المرور مطلوبة',
        ];
    }
}
