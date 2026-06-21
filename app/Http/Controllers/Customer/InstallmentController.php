<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Installment\ListInstallmentContractsRequest;
use App\Http\Resources\InstallmentContractResource;
use App\Models\InstallmentContract;
use Illuminate\Http\JsonResponse;

class InstallmentController extends Controller
{
    public function index(ListInstallmentContractsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', InstallmentContract::class);

        $customer = $request->user();

        $contracts = InstallmentContract::query()
            ->with(['company', 'order'])
            ->where('customer_id', $customer->id)
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString())
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
            'company',
            'order',
            'schedule.penalties',
            'payments',
        ]);

        return response()->json([
            'data' => new InstallmentContractResource($installmentContract),
        ]);
    }
}
