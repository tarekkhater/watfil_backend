<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'                  => 'required|integer|exists:customers,id',
            'payment_type'                 => 'required|in:cash,installment',
            'items'                        => 'required|array|min:1',
            'items.*.company_product_id'   => 'required|integer|exists:company_products,id',
            'items.*.quantity'             => 'required|integer|min:1',
            'discount'                     => 'sometimes|numeric|min:0',
            'notes'                        => 'sometimes|nullable|string|max:2000',
            'governorate_id'               => 'sometimes|nullable|integer|exists:governorates,id',
            'idempotency_key'              => 'sometimes|nullable|string|max:255',
            'source'                       => 'sometimes|array',
            'source.channel'               => 'required_with:source|in:ad,referral,link,direct',
            'source.reference_type'        => 'sometimes|nullable|string|max:255',
            'source.reference_id'          => 'sometimes|nullable|integer',
            'source.metadata'              => 'sometimes|nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'معرّف العميل مطلوب',
            'items.required'       => 'يجب إضافة منتج واحد على الأقل',
            'payment_type.in'      => 'نوع الدفع غير صالح',
        ];
    }
}
