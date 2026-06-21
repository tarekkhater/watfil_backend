<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\Customer;
use App\Models\InstallmentContract;
use App\Models\SuperAdmin;

class InstallmentContractPolicy
{
    public function viewAny(Company|Customer|SuperAdmin $actor): bool
    {
        if ($actor instanceof SuperAdmin) {
            return true;
        }

        return $actor->is_active;
    }

    public function view(Company|Customer|SuperAdmin $actor, InstallmentContract $contract): bool
    {
        if ($actor instanceof SuperAdmin) {
            return true;
        }

        if ($actor instanceof Company) {
            return $actor->is_active && $contract->company_id === $actor->id;
        }

        return $actor->is_active && $contract->customer_id === $actor->id;
    }

    public function recordPayment(Company $company, InstallmentContract $contract): bool
    {
        return $company->is_active
            && $contract->company_id === $company->id
            && in_array($contract->status, ['active', 'defaulted'], true);
    }
}
