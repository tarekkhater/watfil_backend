<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreWithdrawalRequest;
use App\Http\Resources\WalletTransactionResource;
use App\Http\Resources\WithdrawalRequestResource;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use App\Services\Finance\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function transactions(Request $request): JsonResponse
    {
        $company = $request->user();

        $transactions = WalletTransaction::query()
            ->with('meta')
            ->where('company_id', $company->id)
            ->when(
                $request->filled('direction'),
                fn ($query) => $query->where('direction', $request->string('direction')->toString())
            )
            ->when(
                $request->filled('category'),
                fn ($query) => $query->where('category', $request->string('category')->toString())
            )
            ->when(
                $request->filled('from'),
                fn ($query) => $query->whereDate('created_at', '>=', $request->string('from')->toString())
            )
            ->when(
                $request->filled('to'),
                fn ($query) => $query->whereDate('created_at', '<=', $request->string('to')->toString())
            )
            ->latest('id')
            ->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'data' => WalletTransactionResource::collection($transactions->items()),
            'meta' => [
                'total'        => $transactions->total(),
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
                'per_page'     => $transactions->perPage(),
            ],
        ]);
    }

    public function storeWithdrawal(
        StoreWithdrawalRequest $request,
        WithdrawalService $withdrawalService
    ): JsonResponse {
        $this->authorize('create', WithdrawalRequest::class);

        $withdrawalRequest = $withdrawalService->createRequest(
            company: $request->user(),
            amount: (float) $request->validated('amount'),
            idempotencyKey: $request->validated('idempotency_key')
        );

        return response()->json([
            'message' => 'تم إنشاء طلب السحب بنجاح.',
            'data'    => new WithdrawalRequestResource($withdrawalRequest),
        ], 201);
    }
}
