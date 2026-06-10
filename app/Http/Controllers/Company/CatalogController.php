<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierProductResource;
use App\Models\SupplierProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function availableProducts(Request $request): JsonResponse
    {
        $query = SupplierProduct::with(['supplier', 'installmentPlans'])->where('is_active', true);

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $products = $query->latest()->paginate(15);

        return response()->json([
            'data' => SupplierProductResource::collection($products->items()),
            'meta' => [
                'total'        => $products->total(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
            ],
        ]);
    }

    public function mycatalog(): JsonResponse
    {
        $company  = auth()->user();
        $products = $company->catalogProducts()->with(['supplier', 'installmentPlans'])->paginate(15);

        return response()->json([
            'data' => SupplierProductResource::collection($products->items()),
            'meta' => [
                'total'        => $products->total(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
            ],
        ]);
    }

    public function addToMyCatalog(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids'   => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:supplier_products,id',
        ]);

        $company      = auth()->user();
        $activeIds    = SupplierProduct::whereIn('id', $request->product_ids)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        $company->catalogProducts()->syncWithoutDetaching($activeIds);

        return response()->json(['message' => 'تم إضافة المنتجات إلى متجرك بنجاح']);
    }

    public function removeFromMyCatalog(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids'   => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:supplier_products,id',
        ]);

        $company = auth()->user();
        $company->catalogProducts()->detach($request->product_ids);

        return response()->json(['message' => 'تم إزالة المنتجات من متجرك بنجاح']);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_product_id' => 'required|integer|exists:supplier_products,id',
        ]);

        $product = SupplierProduct::findOrFail($request->supplier_product_id);

        if (! $product->is_active) {
            return response()->json(['message' => 'هذا المنتج غير متاح حاليًا'], 422);
        }

        $company = auth()->user();
        $company->catalogProducts()->syncWithoutDetaching([$product->id]);

        return response()->json(['message' => 'تم إضافة المنتج إلى متجرك بنجاح']);
    }

    public function destroy(SupplierProduct $supplierProduct): JsonResponse
    {
        $company = auth()->user();
        $company->catalogProducts()->detach($supplierProduct->id);

        return response()->json(['message' => 'تم إزالة المنتج من متجرك بنجاح']);
    }
}
