<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class PayWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payout_reference' => 'required|string|max:255',
            'note'             => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'payout_reference.required' => 'مرجع التحويل مطلوب.',
        ];
    }
}
