<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstallmentPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'installment_schedule_id' => 'required|integer|exists:installment_schedule,id',
            'amount'                  => 'required|numeric|min:0.01',
            'payment_method'          => 'sometimes|in:cash,transfer,card,other',
            'notes'                   => 'sometimes|nullable|string|max:2000',
            'paid_at'                 => 'sometimes|nullable|date',
            'idempotency_key'         => 'sometimes|nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'installment_schedule_id.required' => 'معرّف قسط الجدول مطلوب',
            'amount.required'                  => 'مبلغ الدفعة مطلوب',
        ];
    }
}
