<?php

namespace App\Policies;

use App\Models\Customer;

class CustomerPolicy
{
    public function viewProfile(Customer $customer, Customer $target): bool
    {
        return $customer->id === $target->id;
    }

    public function updateProfile(Customer $customer, Customer $target): bool
    {
        return $customer->id === $target->id;
    }
}
