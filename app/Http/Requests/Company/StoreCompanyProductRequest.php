<?php

namespace App\Http\Requests\Company;

use App\Models\CompanyProductInstallmentPlan;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyProductRequest extends FormRequest
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
        $allowedMonths = implode(',', CompanyProductInstallmentPlan::ALLOWED_MONTHS);

        return [
            'name'                              => 'required|string|max:255',
            'description'                       => 'nullable|string',
            'image'                               => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'cash_price'                          => 'required|numeric|min:0',
            'category_id'                         => 'nullable|integer|exists:categories,id',
            'is_active'                           => 'nullable|boolean',
            'installment_plans'                   => 'nullable|array',
            'installment_plans.*.months'          => "required|integer|in:{$allowedMonths}|distinct",
            'installment_plans.*.down_payment'    => 'required|numeric|min:0',
            'installment_plans.*.installment_amount' => 'required|numeric|min:0.01',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                         => 'اسم المنتج مطلوب',
            'cash_price.required'                   => 'سعر الكاش مطلوب',
            'cash_price.numeric'                    => 'سعر الكاش يجب أن يكون رقمًا',
            'installment_plans.*.months.required'   => 'مدة التقسيط مطلوبة',
            'installment_plans.*.months.in'         => 'مدة التقسيط غير مسموحة',
            'installment_plans.*.months.distinct'   => 'لا يمكن تكرار نفس مدة التقسيط',
            'installment_plans.*.down_payment.required' => 'المقدم مطلوب',
            'installment_plans.*.installment_amount.required' => 'قيمة القسط مطلوبة',
            'installment_plans.*.installment_amount.min' => 'قيمة القسط يجب أن تكون أكبر من صفر',
        ];
    }
}
