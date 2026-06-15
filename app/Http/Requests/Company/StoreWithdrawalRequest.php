<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'          => ['required', 'numeric', 'min:' . config('finance.min_withdrawal_amount', 100)],
            'idempotency_key' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'قيمة السحب مطلوبة.',
            'amount.numeric'  => 'قيمة السحب يجب أن تكون رقمًا.',
            'amount.min'      => 'قيمة السحب أقل من الحد الأدنى المسموح.',
        ];
    }
}
