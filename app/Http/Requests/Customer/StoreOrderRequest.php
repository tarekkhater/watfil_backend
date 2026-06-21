<?php

namespace App\Http\Requests\Customer;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id'       => 'required|integer|exists:companies,id',
            'product_type'     => ['required', 'string', Rule::in([Order::TYPE_COMPANY_PRODUCT, Order::TYPE_SUPPLIER_PRODUCT])],
            'product_id'       => 'required|integer|min:1',
            'quantity'         => 'sometimes|integer|min:1|max:100',
            'delivery_address' => 'required|string|max:1000',
            'notes'            => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required'       => 'الشركة مطلوبة',
            'company_id.exists'         => 'الشركة غير موجودة',
            'product_type.required'     => 'نوع المنتج مطلوب',
            'product_type.in'           => 'نوع المنتج غير صالح',
            'product_id.required'       => 'المنتج مطلوب',
            'quantity.min'              => 'الكمية يجب أن تكون 1 على الأقل',
            'delivery_address.required' => 'عنوان التوصيل مطلوب',
        ];
    }
}
