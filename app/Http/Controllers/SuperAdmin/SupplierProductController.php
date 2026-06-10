<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreSupplierProductRequest;
use App\Http\Requests\SuperAdmin\UpdateSupplierProductRequest;
use App\Http\Resources\SupplierProductResource;
use App\Models\SupplierProduct;
use App\Support\PublicFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SupplierProduct::with('supplier');

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
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

    public function store(StoreSupplierProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products/supplier', 'public');
        }

        $product = SupplierProduct::create($data);

        return response()->json([
            'message' => 'تم إنشاء المنتج بنجاح',
            'data'    => new SupplierProductResource($product->load('supplier')),
        ], 201);
    }

    public function show(SupplierProduct $supplierProduct): JsonResponse
    {
        return response()->json([
            'data' => new SupplierProductResource($supplierProduct->load('supplier')),
        ]);
    }

    public function update(UpdateSupplierProductRequest $request, SupplierProduct $supplierProduct): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($supplierProduct->image) {
                PublicFile::delete($supplierProduct->image);
            }
            $data['image'] = $request->file('image')->store('products/supplier', 'public');
        }

        $supplierProduct->update($data);

        return response()->json([
            'message' => 'تم تحديث المنتج بنجاح',
            'data'    => new SupplierProductResource($supplierProduct->fresh()->load('supplier')),
        ]);
    }

    public function destroy(SupplierProduct $supplierProduct): JsonResponse
    {
        if ($supplierProduct->image) {
            PublicFile::delete($supplierProduct->image);
        }

        $supplierProduct->delete();

        return response()->json(['message' => 'تم حذف المنتج بنجاح']);
    }

    public function toggleStatus(SupplierProduct $supplierProduct): JsonResponse
    {
        $supplierProduct->update(['is_active' => ! $supplierProduct->is_active]);

        return response()->json([
            'message'   => $supplierProduct->is_active ? 'تم تفعيل المنتج' : 'تم تعطيل المنتج',
            'is_active' => $supplierProduct->is_active,
        ]);
    }
}
