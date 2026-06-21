<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class ListOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'      => 'sometimes|in:pending,processing,completed,cancelled',
            'company_id'  => 'sometimes|integer|exists:companies,id',
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'from'        => 'sometimes|date',
            'to'          => 'sometimes|date|after_or_equal:from',
            'per_page'    => 'sometimes|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'حالة الطلب غير صالحة',
        ];
    }
}
