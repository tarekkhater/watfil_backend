<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyProductRequest;
use App\Http\Requests\Company\UpdateCompanyProductRequest;
use App\Http\Resources\CompanyProductResource;
use App\Models\CompanyProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $company  = auth()->user();
        $products = $company->products()->latest()->paginate(15);

        return response()->json([
            'data' => CompanyProductResource::collection($products->items()),
            'meta' => [
                'total'        => $products->total(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
            ],
        ]);
    }

    public function store(StoreCompanyProductRequest $request): JsonResponse
    {
        $data             = $request->validated();
        $data['company_id'] = auth()->id();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products/company', 'public');
        }

        $product = CompanyProduct::create($data);

        return response()->json([
            'message' => 'تم إضافة المنتج بنجاح',
            'data'    => new CompanyProductResource($product),
        ], 201);
    }

    public function show(CompanyProduct $companyProduct): JsonResponse
    {
        $this->authorizeProduct($companyProduct);

        return response()->json([
            'data' => new CompanyProductResource($companyProduct),
        ]);
    }

    public function update(UpdateCompanyProductRequest $request, CompanyProduct $companyProduct): JsonResponse
    {
        $this->authorizeProduct($companyProduct);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($companyProduct->image) {
                Storage::disk('public')->delete($companyProduct->image);
            }
            $data['image'] = $request->file('image')->store('products/company', 'public');
        }

        $companyProduct->update($data);

        return response()->json([
            'message' => 'تم تحديث المنتج بنجاح',
            'data'    => new CompanyProductResource($companyProduct->fresh()),
        ]);
    }

    public function destroy(CompanyProduct $companyProduct): JsonResponse
    {
        $this->authorizeProduct($companyProduct);

        if ($companyProduct->image) {
            Storage::disk('public')->delete($companyProduct->image);
        }

        $companyProduct->delete();

        return response()->json(['message' => 'تم حذف المنتج بنجاح']);
    }

    private function authorizeProduct(CompanyProduct $product): void
    {
        if ($product->company_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }
    }
}
