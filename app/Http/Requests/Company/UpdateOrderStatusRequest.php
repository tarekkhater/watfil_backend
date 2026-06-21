<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'               => 'required|in:processing,completed,cancelled',
            'note'                 => 'sometimes|nullable|string|max:1000',
            'cancellation_reason'  => 'required_if:status,cancelled|nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in'                          => 'حالة الطلب غير صالحة',
            'cancellation_reason.required_if'    => 'سبب الإلغاء مطلوب عند إلغاء الطلب',
        ];
    }
}
