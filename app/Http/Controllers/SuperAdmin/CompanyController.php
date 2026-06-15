<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\AdjustCompanyWalletRequest;
use App\Http\Requests\SuperAdmin\StoreCompanyRequest;
use App\Http\Requests\SuperAdmin\UpdateCompanyRequest;
use App\Http\Requests\SuperAdmin\UpdateCompanyWalletRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\WalletTransactionResource;
use App\Models\Company;
use App\Models\WalletTransaction;
use App\Services\Finance\WalletPostingService;
use App\Support\PublicFile;
use Illuminate\Http\JsonResponse;

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
        $latestTransactions = WalletTransaction::query()
            ->with('meta')
            ->where('company_id', $company->id)
            ->latest('id')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'company_id'     => $company->id,
                'company_name'   => $company->name,
                'wallet_balance' => $company->wallet_balance,
                'latest_transactions' => WalletTransactionResource::collection($latestTransactions),
            ],
        ]);
    }

    public function updateWallet(
        UpdateCompanyWalletRequest $request,
        Company $company,
        WalletPostingService $walletPostingService
    ): JsonResponse
    {
        $validated = $request->validated();
        $targetBalance = (float) $validated['wallet_balance'];
        $currentBalance = (float) $company->wallet_balance;
        $difference = round(abs($targetBalance - $currentBalance), 2);

        if ($difference > 0) {
            $walletPostingService->post(
                company: $company,
                direction: $targetBalance > $currentBalance ? 'credit' : 'debit',
                amount: $difference,
                category: 'manual_set_balance',
                description: $validated['reason'] ?? 'تعيين مباشر لرصيد المحفظة',
                source: $company,
                actor: $request->user()
            );
        }

        return response()->json([
            'message' => 'تم تحديث رصيد المحفظة بنجاح',
            'data'    => new CompanyResource($company->fresh()->load('governorate')),
        ]);
    }

    public function adjustWallet(
        AdjustCompanyWalletRequest $request,
        Company $company,
        WalletPostingService $walletPostingService
    ): JsonResponse
    {
        $validated = $request->validated();

        $walletPostingService->post(
            company: $company,
            direction: $validated['type'],
            amount: (float) $validated['amount'],
            category: 'manual_adjustment',
            description: $validated['reason'] ?? (
                $validated['type'] === 'credit'
                    ? 'إضافة رصيد إداري'
                    : 'خصم رصيد إداري'
            ),
            idempotencyKey: $validated['idempotency_key'] ?? null,
            source: $company,
            actor: $request->user()
        );

        return response()->json([
            'message' => $validated['type'] === 'credit'
                ? 'تم إضافة الرصيد بنجاح'
                : 'تم خصم الرصيد بنجاح',
            'data' => new CompanyResource($company->fresh()->load('governorate')),
        ]);
    }
}
