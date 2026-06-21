<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\Concerns\ValidatesOrderPayment;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    use ValidatesOrderPayment;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->orderPaymentRules(), [
            'company_id'                   => 'required|integer|exists:companies,id',
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
        ]);
    }

    public function messages(): array
    {
        return array_merge($this->orderPaymentMessages(), [
            'company_id.required' => 'معرّف الشركة مطلوب',
            'items.required'      => 'يجب إضافة منتج واحد على الأقل',
        ]);
    }
}
