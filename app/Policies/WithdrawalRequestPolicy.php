<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\SuperAdmin;
use App\Models\WithdrawalRequest;

class WithdrawalRequestPolicy
{
    public function create(Company $company): bool
    {
        return $company->is_active;
    }

    public function review(SuperAdmin $admin, WithdrawalRequest $withdrawalRequest): bool
    {
        return true;
    }

    public function approve(SuperAdmin $admin, WithdrawalRequest $withdrawalRequest): bool
    {
        return $this->review($admin, $withdrawalRequest);
    }

    public function reject(SuperAdmin $admin, WithdrawalRequest $withdrawalRequest): bool
    {
        return $this->review($admin, $withdrawalRequest);
    }

    public function pay(SuperAdmin $admin, WithdrawalRequest $withdrawalRequest): bool
    {
        return $this->review($admin, $withdrawalRequest);
    }
}
