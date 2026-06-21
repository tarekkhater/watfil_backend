<?php

namespace Tests\Unit;

use App\Models\CompanyProductInstallmentPlan;
use App\Services\Installment\InstallmentContractService;
use Tests\TestCase;

class InstallmentContractServiceTest extends TestCase
{
    public function test_calculate_order_total_from_plan(): void
    {
        $service = $this->app->make(InstallmentContractService::class);

        $plan = new CompanyProductInstallmentPlan([
            'months'             => 12,
            'down_payment'       => 500,
            'installment_amount' => 300,
        ]);

        $this->assertSame(4100.0, $service->calculateOrderTotal($plan));
    }
}
