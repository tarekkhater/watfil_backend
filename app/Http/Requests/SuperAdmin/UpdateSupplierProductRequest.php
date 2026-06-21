<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\SupplierProductInstallmentPlan;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('installment_plans') && is_string($this->installment_plans)) {
            $this->merge([
                'installment_plans' => json_decode($this->installment_plans, true) ?? [],
            ]);
        }
    }

    public function rules(): array
    {
        $allowedMonths = implode(',', SupplierProductInstallmentPlan::ALLOWED_MONTHS);

        return [
            'name'                              => 'sometimes|string|max:255',
            'description'                       => 'nullable|string',
            'image'                               => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'cash_price'                          => 'sometimes|numeric|min:0',
            'supplier_id'                         => 'sometimes|integer|exists:suppliers,id',
            'category_id'                         => 'nullable|integer|exists:categories,id',
            'is_active'                           => 'sometimes|boolean',
            'installment_plans'                   => 'nullable|array',
            'installment_plans.*.months'          => "required|integer|in:{$allowedMonths}|distinct",
            'installment_plans.*.down_payment'    => 'required|numeric|min:0',
            'installment_plans.*.installment_amount' => 'required|numeric|min:0.01',
        ];
    }

    public function messages(): array
    {
        return [
            'cash_price.numeric'                    => 'سعر الكاش يجب أن يكون رقمًا',
            'supplier_id.exists'                    => 'المورد غير موجود',
            'category_id.exists'                      => 'الصنف غير موجود',
            'installment_plans.*.months.required'   => 'مدة التقسيط مطلوبة',
            'installment_plans.*.months.in'         => 'مدة التقسيط غير مسموحة',
            'installment_plans.*.months.distinct'   => 'لا يمكن تكرار نفس مدة التقسيط',
            'installment_plans.*.down_payment.required' => 'المقدم مطلوب',
            'installment_plans.*.installment_amount.required' => 'قيمة القسط مطلوبة',
            'installment_plans.*.installment_amount.min' => 'قيمة القسط يجب أن تكون أكبر من صفر',
        ];
    }
}
