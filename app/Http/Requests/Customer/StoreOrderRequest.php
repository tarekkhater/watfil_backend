<?php

namespace App\Http\Requests\Customer;

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
            'company_id'                   => 'required|integer|exists:companies,id',
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
            'company_id.required' => 'معرّف الشركة مطلوب',
            'items.required'      => 'يجب إضافة منتج واحد على الأقل',
            'payment_type.in'   => 'نوع الدفع غير صالح',
        ];
    }
}
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
