<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\CustomerCompanyLink;

class CustomerCompanyLinkPolicy
{
    public function viewAny(Company $company): bool
    {
        return $company->is_active;
    }

    public function view(Company $company, CustomerCompanyLink $link): bool
    {
        return $link->company_id === $company->id;
    }
}
