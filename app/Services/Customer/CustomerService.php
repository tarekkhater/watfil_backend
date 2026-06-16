<?php

namespace App\Services\Customer;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerCompanyLink;
use App\Models\CustomerProfile;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function register(array $data): Customer
    {
        return DB::transaction(function () use ($data): Customer {
            $customer = Customer::create([
                'phone'    => $data['phone'],
                'email'    => $data['email'] ?? null,
                'password' => $data['password'],
            ]);

            CustomerProfile::create([
                'customer_id'    => $customer->id,
                'full_name'      => $data['full_name'],
                'governorate_id' => $data['governorate_id'] ?? null,
                'city'           => $data['city'] ?? null,
                'address'        => $data['address'] ?? null,
            ]);

            if (! empty($data['company_id'])) {
                $this->linkToCompany($customer, Company::findOrFail($data['company_id']));
            }

            return $customer->load(['profile.governorate', 'companyLinks.company']);
        });
    }

    public function updateProfile(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data): Customer {
            $customerFields = array_intersect_key($data, array_flip(['email']));
            if ($customerFields !== []) {
                $customer->update($customerFields);
            }

            $profileFields = array_intersect_key(
                $data,
                array_flip(['full_name', 'governorate_id', 'city', 'address', 'date_of_birth', 'gender'])
            );

            if ($profileFields !== []) {
                $customer->profile()->updateOrCreate(
                    ['customer_id' => $customer->id],
                    $profileFields
                );
            }

            return $customer->fresh(['profile.governorate', 'companyLinks.company']);
        });
    }

    public function linkToCompany(Customer $customer, Company $company): CustomerCompanyLink
    {
        return CustomerCompanyLink::firstOrCreate(
            [
                'customer_id' => $customer->id,
                'company_id'  => $company->id,
            ],
            [
                'status'    => 'active',
                'linked_at' => now(),
            ]
        );
    }
}
