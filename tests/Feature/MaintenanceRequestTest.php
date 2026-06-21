<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerProfile;
use App\Models\Governorate;
use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MaintenanceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_maintenance_lookups_returns_config(): void
    {
        $this->getJson('/api/public/maintenance/lookups')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'purification_systems',
                    'stages_counts',
                    'primary_problem_types',
                    'malfunction_types',
                    'max_stages',
                ],
            ]);
    }

    public function test_customer_can_submit_full_maintenance_request(): void
    {
        [$customer, $company, $governorate] = $this->createFixture();

        Sanctum::actingAs($customer);

        $payload = $this->validPayload($company->id, $governorate->id, 3);

        $response = $this->postJson('/api/customer/maintenance-requests', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.customer.full_name', 'أحمد محمد')
            ->assertJsonPath('data.customer.city', 'مدينة نصر')
            ->assertJsonPath('data.device.stages_count', 3)
            ->assertJsonPath('data.request.primary_problem_type', 'water_quality')
            ->assertJsonPath('data.status', MaintenanceRequest::STATUS_PENDING);

        $this->assertDatabaseHas('maintenance_requests', [
            'customer_id'          => $customer->id,
            'company_id'           => $company->id,
            'full_name'            => 'أحمد محمد',
            'phone'                => '01012345678',
            'governorate_id'       => $governorate->id,
            'city'                 => 'مدينة نصر',
            'area'                 => 'الحي السابع',
            'purification_system'  => 'ro',
            'stages_count'         => 3,
            'primary_problem_type' => 'water_quality',
            'malfunction_type'     => 'stage_1',
        ]);
    }

    public function test_stage_dates_required_up_to_stages_count(): void
    {
        [$customer, $company, $governorate] = $this->createFixture();

        Sanctum::actingAs($customer);

        $payload = $this->validPayload($company->id, $governorate->id, 2);
        unset($payload['last_stage_change_dates']['stage_2']);

        $this->postJson('/api/customer/maintenance-requests', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['last_stage_change_dates.stage_2']);
    }

    public function test_customer_can_list_and_show_own_maintenance_requests(): void
    {
        [$customer, $company, $governorate] = $this->createFixture();

        Sanctum::actingAs($customer);

        $create = $this->postJson('/api/customer/maintenance-requests', $this->validPayload($company->id, $governorate->id, 1));
        $id     = $create->json('data.id');

        $this->getJson('/api/customer/maintenance-requests')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson("/api/customer/maintenance-requests/{$id}")
            ->assertOk()
            ->assertJsonPath('data.id', $id);
    }

    /** @return array{0: Customer, 1: Company, 2: Governorate} */
    private function createFixture(): array
    {
        $governorate = Governorate::create([
            'name_ar' => 'القاهرة',
            'name_en' => 'Cairo',
        ]);

        $company = Company::create([
            'name'           => 'شركة صيانة',
            'tax_number'     => 'TAX-MNT-001',
            'password'       => 'secret1234',
            'governorate_id' => $governorate->id,
            'is_active'      => true,
        ]);

        $customer = Customer::create([
            'phone'     => '01055556666',
            'password'  => 'secret1234',
            'is_active' => true,
        ]);

        CustomerProfile::create([
            'customer_id'    => $customer->id,
            'full_name'      => 'عميل',
            'governorate_id' => $governorate->id,
        ]);

        return [$customer, $company, $governorate];
    }

    /** @return array<string, mixed> */
    private function validPayload(int $companyId, int $governorateId, int $stagesCount): array
    {
        $dates = [];

        for ($i = 1; $i <= $stagesCount; $i++) {
            $dates["stage_{$i}"] = "2025-0{$i}-15";
        }

        return [
            'company_id'              => $companyId,
            'full_name'               => 'أحمد محمد',
            'phone'                   => '01012345678',
            'governorate_id'          => $governorateId,
            'city'                    => 'مدينة نصر',
            'area'                    => 'الحي السابع',
            'address_details'         => 'شارع 9',
            'device_details'          => 'فلتر 7 مراحل',
            'purification_system'     => 'ro',
            'stages_count'            => $stagesCount,
            'last_stage_change_dates' => $dates,
            'primary_problem_type'    => 'water_quality',
            'malfunction_type'        => 'stage_1',
            'notes'                   => 'المياه بطيئة',
        ];
    }
}
