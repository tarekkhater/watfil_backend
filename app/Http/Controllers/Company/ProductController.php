<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyProductRequest;
use App\Http\Requests\Company\UpdateCompanyProductRequest;
use App\Http\Resources\CompanyProductResource;
use App\Models\CompanyProduct;
use App\Support\PublicFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $company  = auth()->user();
        $products = $company->products()->with(['installmentPlans', 'category.productType'])->latest()->paginate(15);

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
        $data               = $request->validated();
        $installmentPlans   = $data['installment_plans'] ?? [];
        $data['company_id'] = auth()->id();
        unset($data['installment_plans']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products/company', 'public');
        }

        $product = DB::transaction(function () use ($data, $installmentPlans) {
            $product = CompanyProduct::create($data);

            if (! empty($installmentPlans)) {
                $product->installmentPlans()->createMany($installmentPlans);
            }

            return $product;
        });

        return response()->json([
            'message' => 'تم إضافة المنتج بنجاح',
            'data'    => new CompanyProductResource($product->load(['installmentPlans', 'category.productType'])),
        ], 201);
    }

    public function show(CompanyProduct $companyProduct): JsonResponse
    {
        $this->authorizeProduct($companyProduct);

        return response()->json([
            'data' => new CompanyProductResource($companyProduct->load(['installmentPlans', 'category.productType'])),
        ]);
    }

    public function update(UpdateCompanyProductRequest $request, CompanyProduct $companyProduct): JsonResponse
    {
        $this->authorizeProduct($companyProduct);

        $data             = $request->validated();
        $hasInstallmentPlans = array_key_exists('installment_plans', $data);
        $installmentPlans = $data['installment_plans'] ?? [];
        unset($data['installment_plans']);

        if ($request->hasFile('image')) {
            if ($companyProduct->image) {
                PublicFile::delete($companyProduct->image);
            }
            $data['image'] = $request->file('image')->store('products/company', 'public');
        }

        DB::transaction(function () use ($companyProduct, $data, $hasInstallmentPlans, $installmentPlans) {
            $companyProduct->update($data);

            if ($hasInstallmentPlans) {
                $companyProduct->installmentPlans()->delete();

                if (! empty($installmentPlans)) {
                    $companyProduct->installmentPlans()->createMany($installmentPlans);
                }
            }
        });

        return response()->json([
            'message' => 'تم تحديث المنتج بنجاح',
            'data'    => new CompanyProductResource($companyProduct->fresh()->load(['installmentPlans', 'category.productType'])),
        ]);
    }

    public function destroy(CompanyProduct $companyProduct): JsonResponse
    {
        $this->authorizeProduct($companyProduct);

        if ($companyProduct->image) {
            PublicFile::delete($companyProduct->image);
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
