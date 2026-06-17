<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\SuperAdmin;

class OrderPolicy
{
    public function viewAny(Company|Customer|SuperAdmin $actor): bool
    {
        if ($actor instanceof Company || $actor instanceof Customer) {
            return $actor->is_active;
        }

        return true;
    }

    public function view(Company|Customer|SuperAdmin $actor, Order $order): bool
    {
        if ($actor instanceof SuperAdmin) {
            return true;
        }

        if ($actor instanceof Company) {
            return $actor->is_active && $order->company_id === $actor->id;
        }

        return $actor->is_active && $order->customer_id === $actor->id;
    }

    public function create(Company|Customer $actor): bool
    {
        return $actor->is_active;
    }

    public function updateStatus(Company $company, Order $order): bool
    {
        return $company->is_active && $order->company_id === $company->id;
    }
}
