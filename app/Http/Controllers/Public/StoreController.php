<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CompanyProductInstallmentPlanResource;
use App\Http\Resources\InstallmentPlanOptionResource;
use App\Http\Resources\PublicStoreProductResource;
use App\Http\Resources\SupplierResource;
use App\Models\Company;
use App\Models\CompanyProduct;
use App\Support\InstallmentPlanSummary;
use App\Support\PublicFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class StoreController extends Controller
{
    public function products(Request $request, Company $company): JsonResponse
    {
        if (! $company->is_active) {
            return response()->json(['message' => 'هذه الشركة غير متاحة حاليًا'], 404);
        }

        $ownProducts = $this->filteredCompanyProductsQuery($company, $request)
            ->with(['installmentPlans', 'category.productType'])
            ->latest()
            ->get()
            ->map(fn ($product) => $this->mapStoreProduct($product, 'company'));

        $catalogProducts = $this->filteredCatalogProductsQuery($company, $request)
            ->with(['supplier', 'installmentPlans', 'category.productType'])
            ->latest('supplier_products.created_at')
            ->get()
            ->map(fn ($product) => $this->mapStoreProduct($product, 'catalog'));

        $merged = $ownProducts
            ->concat($catalogProducts)
            ->sortByDesc('sort_at')
            ->values()
            ->map(fn ($item) => collect($item)->except('sort_at')->all());

        $page    = (int) $request->get('page', 1);
        $perPage = 15;
        $total   = $merged->count();
        $items   = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'data' => PublicStoreProductResource::collection($paginator->items()),
            'meta' => [
                'total'        => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
            ],
        ]);
    }

    public function installmentPlans(Company $company, CompanyProduct $companyProduct): JsonResponse
    {
        if (! $company->is_active) {
            return response()->json(['message' => 'هذه الشركة غير متاحة حاليًا'], 404);
        }

        if ($companyProduct->company_id !== $company->id || ! $companyProduct->is_active) {
            return response()->json(['message' => 'المنتج غير متاح'], 404);
        }

        $companyProduct->load('installmentPlans');

        $plans = $companyProduct->installmentPlans
            ->map(fn ($plan) => InstallmentPlanSummary::fromModel($plan))
            ->values();

        return response()->json([
            'data' => [
                'product' => [
                    'id'              => $companyProduct->id,
                    'name'            => $companyProduct->name,
                    'cash_price'      => (float) $companyProduct->cash_price,
                    'has_installment' => $plans->isNotEmpty(),
                ],
                'plans' => InstallmentPlanOptionResource::collection($plans),
            ],
        ]);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\CompanyProduct> */
    private function filteredCompanyProductsQuery(Company $company, Request $request)
    {
        $query = $company->products()->where('is_active', true);

        $this->applyProductFilters($query, $request);

        return $query;
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\SupplierProduct> */
    private function filteredCatalogProductsQuery(Company $company, Request $request)
    {
        $query = $company->catalogProducts()->where('is_active', true);

        $this->applyProductFilters($query, $request, 'supplier_products');

        return $query;
    }

    /** @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $query */
    private function applyProductFilters($query, Request $request, ?string $table = null): void
    {
        $prefix = $table ? "{$table}." : '';

        if ($request->filled('category_id')) {
            $query->where("{$prefix}category_id", (int) $request->input('category_id'));
        }

        if ($request->filled('product_type_id')) {
            $typeId = (int) $request->input('product_type_id');

            $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('product_type_id', $typeId));
        }

        if ($request->filled('number_of_stages')) {
            $stages = (int) $request->input('number_of_stages');

            $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('number_of_stages', $stages));
        }
    }

    /** @return array<string, mixed> */
    private function mapStoreProduct(object $product, string $source): array
    {
        return [
            'id'                => $product->id,
            'source'            => $source,
            'name'              => $product->name,
            'description'       => $product->description,
            'image'             => PublicFile::url($product->image),
            'cash_price'        => $product->cash_price,
            'has_installment'   => $product->installmentPlans->isNotEmpty(),
            'installment_plans' => CompanyProductInstallmentPlanResource::collection($product->installmentPlans)->resolve(),
            'category'          => $product->relationLoaded('category') && $product->category
                ? (new CategoryResource($product->category))->resolve()
                : null,
            'supplier'          => $source === 'catalog' && $product->relationLoaded('supplier') && $product->supplier
                ? (new SupplierResource($product->supplier))->resolve()
                : null,
            'created_at'        => $product->created_at?->toDateTimeString(),
            'sort_at'           => $product->created_at,
        ];
    }
}
