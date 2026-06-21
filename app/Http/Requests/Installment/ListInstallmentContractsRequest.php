<?php

namespace App\Http\Requests\Installment;

use Illuminate\Foundation\Http\FormRequest;

class ListInstallmentContractsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'     => 'sometimes|in:active,completed,defaulted,cancelled',
            'company_id' => 'sometimes|integer|exists:companies,id',
            'customer_id'=> 'sometimes|integer|exists:customers,id',
            'from'       => 'sometimes|date',
            'to'         => 'sometimes|date',
            'per_page'   => 'sometimes|integer|min:1|max:100',
        ];
    }
}
