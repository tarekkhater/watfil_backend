<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_balance' => 'required|numeric|min:0',
            'reason'         => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_balance.required' => 'رصيد المحفظة مطلوب',
            'wallet_balance.numeric'  => 'رصيد المحفظة يجب أن يكون رقماً',
            'wallet_balance.min'      => 'رصيد المحفظة لا يمكن أن يكون سالباً',
        ];
    }
}
