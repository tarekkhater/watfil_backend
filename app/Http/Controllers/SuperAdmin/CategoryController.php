<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreCategoryRequest;
use App\Http\Requests\SuperAdmin\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->with(['productType', 'parent'])
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
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->orderBy('name')
            ->paginate((int) $request->input('per_page', 50));

        return response()->json([
            'data' => CategoryResource::collection($categories->items()),
            'meta' => [
                'total'        => $categories->total(),
                'current_page' => $categories->currentPage(),
                'last_page'    => $categories->lastPage(),
                'per_page'     => $categories->perPage(),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->assertValidParent(null, $data['parent_category_id'] ?? null);

        $category = Category::create($data);

        return response()->json([
            'message' => 'تم إنشاء الصنف بنجاح',
            'data'    => new CategoryResource($category->load(['productType', 'parent'])),
        ], 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'data' => new CategoryResource(
                $category->load(['productType', 'parent', 'children.productType'])
            ),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('parent_category_id', $data)) {
            $this->assertValidParent($category->id, $data['parent_category_id']);
        }

        $category->update($data);

        return response()->json([
            'message' => 'تم تحديث الصنف بنجاح',
            'data'    => new CategoryResource($category->fresh()->load(['productType', 'parent'])),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->children()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف صنف له أصناف فرعية.'], 422);
        }

        if ($category->companyProducts()->exists() || $category->supplierProducts()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف صنف مرتبط بمنتجات.'], 422);
        }

        $category->delete();

        return response()->json(['message' => 'تم حذف الصنف بنجاح']);
    }

    private function assertValidParent(?int $categoryId, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($categoryId !== null && $parentId === $categoryId) {
            throw ValidationException::withMessages([
                'parent_category_id' => ['لا يمكن أن يكون الصنف أباً لنفسه.'],
            ]);
        }

        if ($categoryId !== null) {
            $cursor = Category::find($parentId);

            while ($cursor) {
                if ($cursor->id === $categoryId) {
                    throw ValidationException::withMessages([
                        'parent_category_id' => ['لا يمكن إنشاء حلقة في شجرة الأصناف.'],
                    ]);
                }

                $cursor = $cursor->parent;
            }
        }
    }
}
