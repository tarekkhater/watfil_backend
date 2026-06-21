<?php

namespace App\Http\Requests\Concerns;

use App\Models\CompanyProductInstallmentPlan;

trait ValidatesOrderPayment
{
    /** @return array<string, mixed> */
    protected function orderPaymentRules(): array
    {
        $allowedMonths = implode(',', CompanyProductInstallmentPlan::ALLOWED_MONTHS);

        return [
            'payment_type'                        => 'required|in:cash,installment',
            'installment_plan'                    => 'required_if:payment_type,installment|prohibited_if:payment_type,cash|nullable|array',
            'installment_plan.months'             => "required_with:installment_plan|integer|in:{$allowedMonths}",
            'installment_plan.down_payment'       => 'required_with:installment_plan|numeric|min:0',
            'installment_plan.installment_amount' => 'required_with:installment_plan|numeric|min:0.01',
        ];
    }

    /** @return array<string, string> */
    protected function orderPaymentMessages(): array
    {
        return [
            'payment_type.required'                   => 'نوع الدفع مطلوب',
            'payment_type.in'                         => 'نوع الدفع غير صالح',
            'installment_plan.required_if'            => 'خطة التقسيط مطلوبة عند اختيار الدفع بالتقسيط',
            'installment_plan.prohibited_if'          => 'لا يمكن إرسال خطة تقسيط مع الدفع كاش',
            'installment_plan.months.required_with'   => 'مدة التقسيط مطلوبة',
            'installment_plan.months.in'              => 'مدة التقسيط غير مسموحة',
            'installment_plan.down_payment.required_with' => 'قيمة المقدم مطلوبة',
            'installment_plan.installment_amount.required_with' => 'قيمة القسط الشهري مطلوبة',
            'installment_plan.installment_amount.min' => 'قيمة القسط الشهري يجب أن تكون أكبر من صفر',
        ];
    }
}
