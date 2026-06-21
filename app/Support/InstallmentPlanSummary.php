<?php

namespace App\Support;

use App\Models\CompanyProductInstallmentPlan;

class InstallmentPlanSummary
{
    /**
     * @return array{
     *     months: int,
     *     down_payment: float,
     *     installment_amount: float,
     *     remaining_amount: float,
     *     total_amount: float
     * }
     */
    public static function fromValues(int $months, float $downPayment, float $installmentAmount): array
    {
        $remainingAmount = round($months * $installmentAmount, 2);
        $totalAmount     = round($downPayment + $remainingAmount, 2);

        return [
            'months'             => $months,
            'down_payment'       => round($downPayment, 2),
            'installment_amount' => round($installmentAmount, 2),
            'remaining_amount'   => $remainingAmount,
            'total_amount'       => $totalAmount,
        ];
    }

    public static function fromModel(CompanyProductInstallmentPlan $plan): array
    {
        return self::fromValues(
            (int) $plan->months,
            (float) $plan->down_payment,
            (float) $plan->installment_amount
        );
    }

    /**
     * @param array{months: int|string, down_payment: float|string, installment_amount: float|string} $selected
     */
    public static function matches(array $selected, CompanyProductInstallmentPlan $plan): bool
    {
        return (int) $selected['months'] === (int) $plan->months
            && abs((float) $selected['down_payment'] - (float) $plan->down_payment) < 0.01
            && abs((float) $selected['installment_amount'] - (float) $plan->installment_amount) < 0.01;
    }

    /** @param array{total_amount: float} $plan */
    public static function lineTotal(int $quantity, array $plan): float
    {
        return round($quantity * (float) $plan['total_amount'], 2);
    }
}
