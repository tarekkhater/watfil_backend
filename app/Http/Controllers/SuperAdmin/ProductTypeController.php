<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreProductTypeRequest;
use App\Http\Requests\SuperAdmin\UpdateProductTypeRequest;
use App\Http\Resources\ProductTypeResource;
use App\Models\ProductType;
use Illuminate\Http\JsonResponse;

class ProductTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = ProductType::query()->orderBy('id')->get();

        return response()->json([
            'data' => ProductTypeResource::collection($types),
        ]);
    }

    public function store(StoreProductTypeRequest $request): JsonResponse
    {
        $type = ProductType::create($request->validated());

        return response()->json([
            'message' => 'تم إنشاء نوع المنتج بنجاح',
            'data'    => new ProductTypeResource($type),
        ], 201);
    }

    public function show(ProductType $productType): JsonResponse
    {
        return response()->json([
            'data' => new ProductTypeResource($productType->loadCount('categories')),
        ]);
    }

    public function update(UpdateProductTypeRequest $request, ProductType $productType): JsonResponse
    {
        $productType->update($request->validated());

        return response()->json([
            'message' => 'تم تحديث نوع المنتج بنجاح',
            'data'    => new ProductTypeResource($productType->fresh()),
        ]);
    }

    public function destroy(ProductType $productType): JsonResponse
    {
        if ($productType->categories()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف نوع مرتبط بأصناف. احذف الأصناف أولاً.',
            ], 422);
        }

        $productType->delete();

        return response()->json(['message' => 'تم حذف نوع المنتج بنجاح']);
    }
}
