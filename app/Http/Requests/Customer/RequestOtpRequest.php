<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string|regex:/^01[0-9]{9}$/|unique:customers,phone',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.regex'    => 'رقم الهاتف غير صالح',
            'phone.unique'   => 'رقم الهاتف مسجل بالفعل',
        ];
    }
}
