<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\AdjustCompanyWalletRequest;
use App\Http\Requests\SuperAdmin\StoreCompanyRequest;
use App\Http\Requests\SuperAdmin\UpdateCompanyRequest;
use App\Http\Requests\SuperAdmin\UpdateCompanyWalletRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Support\PublicFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function index(): JsonResponse
    {
        $companies = Company::with('governorate')->latest()->paginate(15);

        return response()->json([
            'data'  => CompanyResource::collection($companies->items()),
            'meta'  => [
                'total'        => $companies->total(),
                'current_page' => $companies->currentPage(),
                'last_page'    => $companies->lastPage(),
                'per_page'     => $companies->perPage(),
            ],
        ]);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos/companies', 'public');
        }

        $company = Company::create($data);

        return response()->json([
            'message' => 'تم إنشاء الشركة بنجاح',
            'data'    => new CompanyResource($company->load('governorate')),
        ], 201);
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json([
            'data' => new CompanyResource($company->load('governorate')),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                PublicFile::delete($company->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos/companies', 'public');
        }

        $company->update($data);

        return response()->json([
            'message' => 'تم تحديث الشركة بنجاح',
            'data'    => new CompanyResource($company->fresh()->load('governorate')),
        ]);
    }

    public function destroy(Company $company): JsonResponse
    {
        if ($company->logo) {
            PublicFile::delete($company->logo);
        }

        $company->delete();

        return response()->json(['message' => 'تم حذف الشركة بنجاح']);
    }

    public function toggleStatus(Company $company): JsonResponse
    {
        $company->update(['is_active' => ! $company->is_active]);

        return response()->json([
            'message'   => $company->is_active ? 'تم تفعيل الشركة' : 'تم تعطيل الشركة',
            'is_active' => $company->is_active,
        ]);
    }

    public function showWallet(Company $company): JsonResponse
    {
        return response()->json([
            'data' => [
                'company_id'     => $company->id,
                'company_name'   => $company->name,
                'wallet_balance' => $company->wallet_balance,
            ],
        ]);
    }

    public function updateWallet(UpdateCompanyWalletRequest $request, Company $company): JsonResponse
    {
        $company->update($request->validated());

        return response()->json([
            'message' => 'تم تحديث رصيد المحفظة بنجاح',
            'data'    => new CompanyResource($company->fresh()->load('governorate')),
        ]);
    }

    public function adjustWallet(AdjustCompanyWalletRequest $request, Company $company): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($company, $validated) {
            $company = Company::lockForUpdate()->findOrFail($company->id);

            if ($validated['type'] === 'credit') {
                $company->increment('wallet_balance', $validated['amount']);
            } else {
                if ($company->wallet_balance < $validated['amount']) {
                    abort(422, 'رصيد المحفظة غير كافٍ');
                }

                $company->decrement('wallet_balance', $validated['amount']);
            }
        });

        return response()->json([
            'message' => $validated['type'] === 'credit'
                ? 'تم إضافة الرصيد بنجاح'
                : 'تم خصم الرصيد بنجاح',
            'data' => new CompanyResource($company->fresh()->load('governorate')),
        ]);
    }
}
