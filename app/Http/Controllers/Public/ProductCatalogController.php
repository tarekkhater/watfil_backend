<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductTypeResource;
use App\Models\Category;
use App\Models\ProductType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    public function productTypes(): JsonResponse
    {
        $types = ProductType::query()->orderBy('id')->get();

        return response()->json([
            'data' => ProductTypeResource::collection($types),
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->with('productType')
            ->when(
                $request->filled('product_type_id'),
                fn ($query) => $query->where('product_type_id', (int) $request->input('product_type_id'))
            )
            ->when(
                $request->has('parent_category_id'),
                function ($query) use ($request) {
                    $parentId = $request->input('parent_category_id');

                    if ($parentId === null || $parentId === '' || (int) $parentId === 0) {
                        $query->whereNull('parent_category_id');
                    } else {
                        $query->where('parent_category_id', (int) $parentId);
                    }
                }
            )
            ->when(
                $request->filled('number_of_stages'),
                fn ($query) => $query->where('number_of_stages', (int) $request->input('number_of_stages'))
            )
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }
}
