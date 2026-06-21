<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyProduct;
use App\Models\Governorate;
use App\Models\ProductType;
use App\Models\SuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_create_product_types_and_categories(): void
    {
        $this->seed([
            \Database\Seeders\ProductTypeSeeder::class,
            \Database\Seeders\CategorySeeder::class,
        ]);

        $this->assertDatabaseCount('product_types', 3);
        $this->assertDatabaseHas('product_types', ['name' => 'device', 'name_ar' => 'فلتر مياه']);
        $this->assertGreaterThan(10, Category::count());
    }

    public function test_public_can_list_product_types_and_categories(): void
    {
        $this->seedProductCatalog();

        $this->getJson('/api/public/product-types')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'device');

        $deviceType = ProductType::where('name', 'device')->first();

        $this->getJson("/api/public/categories?product_type_id={$deviceType->id}&parent_category_id=0")
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'product_type_id']]]);
    }

    public function test_admin_can_manage_product_types_and_categories(): void
    {
        Sanctum::actingAs($this->createSuperAdmin());

        $this->postJson('/api/super-admin/product-types', [
            'name'    => 'custom_type',
            'name_ar' => 'نوع مخصص',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'custom_type');

        $typeId = ProductType::where('name', 'custom_type')->value('id');

        $this->postJson('/api/super-admin/categories', [
            'name'            => 'صنف تجريبي',
            'product_type_id' => $typeId,
            'parent_category_id' => 0,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'صنف تجريبي');

        $this->getJson('/api/super-admin/categories?product_type_id='.$typeId)
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_store_products_can_be_filtered_by_category_and_type(): void
    {
        [$company, $deviceCategory, $accessoryCategory] = $this->createStoreFixture();

        $deviceProduct = CompanyProduct::create([
            'company_id'  => $company->id,
            'name'        => 'جهاز RO',
            'cash_price'  => 4500,
            'category_id' => $deviceCategory->id,
            'is_active'   => true,
        ]);

        CompanyProduct::create([
            'company_id'  => $company->id,
            'name'        => 'كلمب',
            'cash_price'  => 50,
            'category_id' => $accessoryCategory->id,
            'is_active'   => true,
        ]);

        $this->getJson("/api/public/companies/{$company->id}/products?category_id={$deviceCategory->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $deviceProduct->id);

        $deviceTypeId = $deviceCategory->product_type_id;

        $this->getJson("/api/public/companies/{$company->id}/products?product_type_id={$deviceTypeId}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_company_product_can_be_created_with_category(): void
    {
        [$company, $deviceCategory] = $this->createStoreFixture(withCompanyAuth: true);

        $this->postJson('/api/company/products', [
            'name'        => 'منتج مصنف',
            'cash_price'  => 1200,
            'category_id' => $deviceCategory->id,
            'is_active'   => true,
        ])->assertCreated()
            ->assertJsonPath('data.category.id', $deviceCategory->id);

        $this->assertDatabaseHas('company_products', [
            'name'        => 'منتج مصنف',
            'category_id' => $deviceCategory->id,
        ]);
    }

    public function test_admin_can_create_supplier_product_with_category(): void
    {
        Sanctum::actingAs($this->createSuperAdmin());

        $this->seedProductCatalog();

        $supplier = \App\Models\Supplier::create([
            'name'      => 'مورد تجريبي',
            'phone'     => '01011112222',
            'is_active' => true,
        ]);

        $category = Category::where('name', 'RO devices')->firstOrFail();

        $this->postJson('/api/super-admin/supplier-products', [
            'name'        => 'فلتر مورد',
            'cash_price'  => 5000,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ])->assertCreated()
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.category.product_type_id', $category->product_type_id);

        $this->assertDatabaseHas('supplier_products', [
            'name'        => 'فلتر مورد',
            'category_id' => $category->id,
        ]);
    }

    /** @return array{0: Company, 1: Category, 2: Category} */
    private function createStoreFixture(bool $withCompanyAuth = false): array
    {
        $this->seedProductCatalog();

        $governorate = Governorate::create([
            'name_ar' => 'القاهرة',
            'name_en' => 'Cairo',
        ]);

        $company = Company::create([
            'name'           => 'شركة كatalog',
            'tax_number'     => 'CAT-001',
            'password'       => 'secret1234',
            'governorate_id' => $governorate->id,
            'is_active'      => true,
        ]);

        $deviceCategory = Category::where('name', 'RO devices')->firstOrFail();
        $accessoryCategory = Category::where('name', 'اصلاح')->firstOrFail();

        if ($withCompanyAuth) {
            Sanctum::actingAs($company);
        }

        return [$company, $deviceCategory, $accessoryCategory];
    }

    private function seedProductCatalog(): void
    {
        $this->seed([
            \Database\Seeders\ProductTypeSeeder::class,
            \Database\Seeders\CategorySeeder::class,
        ]);
    }

    private function createSuperAdmin(): SuperAdmin
    {
        return SuperAdmin::create([
            'name'     => 'Admin',
            'email'    => 'admin@watfil.test',
            'password' => 'secret1234',
        ]);
    }
}
