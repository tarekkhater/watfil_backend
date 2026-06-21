<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyProductInstallmentPlanResource;
use App\Http\Resources\PublicStoreProductResource;
use App\Http\Resources\SupplierResource;
use App\Models\Company;
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

        $ownProducts = $company->products()
            ->where('is_active', true)
            ->with('installmentPlans')
            ->latest()
            ->get()
            ->map(fn ($product) => [
                'id'                => $product->id,
                'source'            => 'company',
                'name'              => $product->name,
                'description'       => $product->description,
                'image'             => PublicFile::url($product->image),
                'cash_price'        => $product->cash_price,
                'installment_plans' => CompanyProductInstallmentPlanResource::collection($product->installmentPlans)->resolve(),
                'supplier'          => null,
                'created_at'        => $product->created_at?->toDateTimeString(),
                'sort_at'           => $product->created_at,
            ]);

        $catalogProducts = $company->catalogProducts()
            ->where('is_active', true)
            ->with(['supplier', 'installmentPlans'])
            ->latest('supplier_products.created_at')
            ->get()
            ->map(fn ($product) => [
                'id'                => $product->id,
                'source'            => 'catalog',
                'name'              => $product->name,
                'description'       => $product->description,
                'image'             => PublicFile::url($product->image),
                'cash_price'        => $product->cash_price,
                'installment_plans' => CompanyProductInstallmentPlanResource::collection($product->installmentPlans)->resolve(),
                'supplier'          => $product->relationLoaded('supplier') && $product->supplier
                    ? (new SupplierResource($product->supplier))->resolve()
                    : null,
                'created_at'        => $product->created_at?->toDateTimeString(),
                'sort_at'           => $product->created_at,
            ]);

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
}
