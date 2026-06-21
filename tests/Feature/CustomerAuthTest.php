<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerCompanyLink;
use App\Models\CustomerProfile;
use App\Models\Governorate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_receive_token(): void
    {
        $governorate = $this->createGovernorate();
        $company = $this->createCompany();

        $payload = [
            'phone'                 => '01012345678',
            'email'                 => 'customer@example.com',
            'password'              => 'secret1234',
            'password_confirmation' => 'secret1234',
            'full_name'             => 'أحمد محمد',
            'governorate_id'        => $governorate->id,
            'city'                  => 'القاهرة',
            'company_id'            => $company->id,
        ];

        $this->postJson('/api/customer/register', $payload)
            ->assertCreated()
            ->assertJsonPath('customer.phone', '01012345678')
            ->assertJsonPath('customer.profile.full_name', 'أحمد محمد')
            ->assertJsonStructure(['token', 'customer']);

        $this->assertDatabaseHas('customers', [
            'phone' => '01012345678',
            'email' => 'customer@example.com',
        ]);

        $this->assertDatabaseHas('customer_profiles', [
            'full_name' => 'أحمد محمد',
            'city'      => 'القاهرة',
        ]);

        $this->assertDatabaseHas('customer_company_links', [
            'company_id' => $company->id,
            'status'     => 'active',
        ]);
    }

    public function test_register_rejects_duplicate_phone(): void
    {
        $this->createCustomer('01099998888');

        $this->postJson('/api/customer/register', [
            'phone'                 => '01099998888',
            'password'              => 'secret1234',
            'password_confirmation' => 'secret1234',
            'full_name'             => 'عميل آخر',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_customer_can_login_with_valid_credentials(): void
    {
        $customer = $this->createCustomer('01011112222', 'secret1234');

        $this->postJson('/api/customer/login', [
            'phone'    => '01011112222',
            'password' => 'secret1234',
        ])->assertOk()
            ->assertJsonPath('customer.id', $customer->id)
            ->assertJsonStructure(['token', 'customer']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->createCustomer('01033334444', 'secret1234');

        $this->postJson('/api/customer/login', [
            'phone'    => '01033334444',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_login_rejects_inactive_customer(): void
    {
        $customer = $this->createCustomer('01055556666', 'secret1234');
        $customer->update(['is_active' => false]);

        $this->postJson('/api/customer/login', [
            'phone'    => '01055556666',
            'password' => 'secret1234',
        ])->assertForbidden()
            ->assertJsonPath('message', 'حسابك موقوف. تواصل مع الإدارة.');
    }

    public function test_customer_can_view_and_update_profile(): void
    {
        $governorate = $this->createGovernorate();
        $customer = $this->createCustomer('01077778888', 'secret1234');

        Sanctum::actingAs($customer);

        $this->getJson('/api/customer/me')
            ->assertOk()
            ->assertJsonPath('data.phone', '01077778888');

        $this->patchJson('/api/customer/profile', [
            'full_name'      => 'محمد علي',
            'governorate_id' => $governorate->id,
            'email'          => 'updated@example.com',
        ])->assertOk()
            ->assertJsonPath('data.profile.full_name', 'محمد علي')
            ->assertJsonPath('data.email', 'updated@example.com');

        $this->assertDatabaseHas('customer_profiles', [
            'customer_id' => $customer->id,
            'full_name'   => 'محمد علي',
        ]);
    }

    public function test_company_token_cannot_access_customer_routes(): void
    {
        $company = $this->createCompany();

        Sanctum::actingAs($company);

        $this->getJson('/api/customer/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'غير مصرح. هذه المنطقة للعملاء فقط.');
    }

    public function test_company_lists_only_linked_customers(): void
    {
        $companyA = $this->createCompany('111111111');
        $companyB = $this->createCompany('222222222');

        $linkedCustomer = $this->createCustomer('01012121212');
        $otherCustomer = $this->createCustomer('01034343434');

        CustomerCompanyLink::create([
            'customer_id' => $linkedCustomer->id,
            'company_id'  => $companyA->id,
            'status'      => 'active',
            'linked_at'   => now(),
        ]);

        CustomerCompanyLink::create([
            'customer_id' => $otherCustomer->id,
            'company_id'  => $companyB->id,
            'status'      => 'active',
            'linked_at'   => now(),
        ]);

        Sanctum::actingAs($companyA);

        $this->getJson('/api/company/customers')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.customer.id', $linkedCustomer->id);
    }

    public function test_company_customer_list_supports_search_and_status_filter(): void
    {
        $company = $this->createCompany();

        $activeCustomer = $this->createCustomer('01056565656', 'secret1234', 'سارة أحمد');
        $inactiveCustomer = $this->createCustomer('01078787878', 'secret1234', 'خالد محمود');

        CustomerCompanyLink::create([
            'customer_id' => $activeCustomer->id,
            'company_id'  => $company->id,
            'status'      => 'active',
            'linked_at'   => now(),
        ]);

        CustomerCompanyLink::create([
            'customer_id' => $inactiveCustomer->id,
            'company_id'  => $company->id,
            'status'      => 'inactive',
            'linked_at'   => now(),
        ]);

        Sanctum::actingAs($company);

        $this->getJson('/api/company/customers?status=active&search=سارة')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.customer.profile.full_name', 'سارة أحمد');
    }

    private function createGovernorate(): Governorate
    {
        return Governorate::create([
            'name_ar' => 'القاهرة',
            'name_en' => 'Cairo',
        ]);
    }

    private function createCompany(string $taxNumber = '123456789'): Company
    {
        $governorate = Governorate::first() ?? $this->createGovernorate();

        return Company::create([
            'name'           => 'شركة واتفيل',
            'tax_number'     => $taxNumber,
            'password'       => 'secret1234',
            'governorate_id' => $governorate->id,
            'is_active'      => true,
            'wallet_balance' => 0,
        ]);
    }

    private function createCustomer(
        string $phone,
        string $password = 'secret1234',
        string $fullName = 'عميل تجريبي'
    ): Customer {
        $customer = Customer::create([
            'phone'    => $phone,
            'password' => $password,
            'is_active' => true,
        ]);

        CustomerProfile::create([
            'customer_id' => $customer->id,
            'full_name' => $fullName,
        ]);

        return $customer->load('profile');
    }
}
