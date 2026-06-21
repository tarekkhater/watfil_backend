<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreInstallmentPaymentRequest;
use App\Http\Requests\Installment\ListInstallmentContractsRequest;
use App\Http\Resources\InstallmentContractResource;
use App\Http\Resources\InstallmentPaymentResource;
use App\Models\InstallmentContract;
use App\Services\Installment\InstallmentContractService;
use Illuminate\Http\JsonResponse;

class InstallmentController extends Controller
{
    public function __construct(
        private readonly InstallmentContractService $installmentContractService
    ) {
    }

    public function index(ListInstallmentContractsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', InstallmentContract::class);

        $company = $request->user();

        $contracts = InstallmentContract::query()
            ->with(['customer.profile', 'order'])
            ->where('company_id', $company->id)
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString())
            )
            ->when(
                $request->filled('customer_id'),
                fn ($query) => $query->where('customer_id', (int) $request->input('customer_id'))
            )
            ->when(
                $request->filled('from'),
                fn ($query) => $query->whereDate('created_at', '>=', $request->input('from'))
            )
            ->when(
                $request->filled('to'),
                fn ($query) => $query->whereDate('created_at', '<=', $request->input('to'))
            )
            ->latest('id')
            ->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'data' => InstallmentContractResource::collection($contracts->items()),
            'meta' => [
                'total'        => $contracts->total(),
                'current_page' => $contracts->currentPage(),
                'last_page'    => $contracts->lastPage(),
                'per_page'     => $contracts->perPage(),
            ],
        ]);
    }

    public function show(InstallmentContract $installmentContract): JsonResponse
    {
        $this->authorize('view', $installmentContract);

        $installmentContract->load([
            'customer.profile',
            'order',
            'schedule.penalties',
            'payments',
        ]);

        return response()->json([
            'data' => new InstallmentContractResource($installmentContract),
        ]);
    }

    public function storePayment(
        StoreInstallmentPaymentRequest $request,
        InstallmentContract $installmentContract
    ): JsonResponse {
        $this->authorize('recordPayment', $installmentContract);

        $data = $request->validated();

        $payment = $this->installmentContractService->recordPayment(
            $installmentContract,
            $data,
            $request->user(),
            $data['idempotency_key'] ?? null
        );

        return response()->json([
            'message' => 'تم تسجيل الدفعة بنجاح',
            'data'    => new InstallmentPaymentResource($payment),
        ], 201);
    }
}
