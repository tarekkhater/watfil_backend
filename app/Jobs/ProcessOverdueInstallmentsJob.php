<?php

namespace App\Jobs;

use App\Services\Installment\InstallmentContractService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessOverdueInstallmentsJob implements ShouldQueue
{
    use Queueable;

    public function handle(InstallmentContractService $installmentContractService): void
    {
        $installmentContractService->processOverdue();
    }
}
