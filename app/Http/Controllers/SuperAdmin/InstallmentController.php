<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Installment\ListInstallmentContractsRequest;
use App\Http\Resources\InstallmentContractResource;
use App\Models\InstallmentContract;
use App\Models\InstallmentSchedule;
use Illuminate\Http\JsonResponse;

class InstallmentController extends Controller
{
    public function index(ListInstallmentContractsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', InstallmentContract::class);

        $contracts = InstallmentContract::query()
            ->with(['company', 'customer.profile', 'order'])
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString())
            )
            ->when(
                $request->filled('company_id'),
                fn ($query) => $query->where('company_id', (int) $request->input('company_id'))
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
            'company',
            'customer.profile',
            'order',
            'schedule.penalties',
            'payments',
        ]);

        return response()->json([
            'data' => new InstallmentContractResource($installmentContract),
        ]);
    }

    public function overdueSummary(): JsonResponse
    {
        $this->authorize('viewAny', InstallmentContract::class);

        $overdueSchedules = InstallmentSchedule::query()
            ->where('status', 'overdue')
            ->whereHas('contract', fn ($query) => $query->whereIn('status', ['active', 'defaulted']))
            ->count();

        $defaultedContracts = InstallmentContract::query()
            ->where('status', 'defaulted')
            ->count();

        return response()->json([
            'data' => [
                'overdue_schedules'   => $overdueSchedules,
                'defaulted_contracts' => $defaultedContracts,
            ],
        ]);
    }
}
