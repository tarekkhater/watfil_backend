<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustCompanyWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'type'   => 'required|in:credit,debit',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'المبلغ مطلوب',
            'amount.numeric'  => 'المبلغ يجب أن يكون رقماً',
            'amount.min'      => 'المبلغ يجب أن يكون أكبر من صفر',
            'type.required'   => 'نوع العملية مطلوب',
            'type.in'         => 'نوع العملية يجب أن يكون credit أو debit',
        ];
    }
}
