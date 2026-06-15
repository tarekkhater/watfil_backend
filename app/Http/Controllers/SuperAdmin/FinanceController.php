<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\ApproveWithdrawalRequest;
use App\Http\Requests\SuperAdmin\PayWithdrawalRequest;
use App\Http\Requests\SuperAdmin\RejectWithdrawalRequest;
use App\Http\Resources\WalletTransactionResource;
use App\Http\Resources\WithdrawalRequestResource;
use App\Models\CommissionEvent;
use App\Models\Company;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use App\Services\Finance\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function companyWalletTransactions(Request $request, Company $company): JsonResponse
    {
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

    public function commissionSummary(Request $request): JsonResponse
    {
        $dateFrom = $request->input('from');
        $dateTo = $request->input('to');

        $events = CommissionEvent::query()
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo));

        $totals = (clone $events)
            ->selectRaw('COUNT(*) as events_count, COALESCE(SUM(gross_amount), 0) as gross_total, COALESCE(SUM(commission_amount), 0) as commission_total, COALESCE(SUM(net_amount), 0) as net_total')
            ->first();

        $byCompany = (clone $events)
            ->join('companies', 'companies.id', '=', 'commission_events.company_id')
            ->groupBy('commission_events.company_id', 'companies.name')
            ->orderByDesc(DB::raw('SUM(commission_events.commission_amount)'))
            ->get([
                'commission_events.company_id',
                'companies.name as company_name',
                DB::raw('COUNT(*) as events_count'),
                DB::raw('COALESCE(SUM(commission_events.commission_amount), 0) as commission_total'),
            ]);

        return response()->json([
            'data' => [
                'events_count'      => (int) ($totals->events_count ?? 0),
                'gross_total'       => (float) ($totals->gross_total ?? 0),
                'commission_total'  => (float) ($totals->commission_total ?? 0),
                'net_total'         => (float) ($totals->net_total ?? 0),
                'commission_by_company' => $byCompany,
            ],
        ]);
    }

    public function withdrawalRequests(Request $request): JsonResponse
    {
        $requests = WithdrawalRequest::query()
            ->with(['company', 'reservedTransaction', 'releaseTransaction', 'audits'])
            ->when(
                $request->filled('company_id'),
                fn ($query) => $query->where('company_id', (int) $request->input('company_id'))
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString())
            )
            ->latest('id')
            ->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'data' => WithdrawalRequestResource::collection($requests->items()),
            'meta' => [
                'total'        => $requests->total(),
                'current_page' => $requests->currentPage(),
                'last_page'    => $requests->lastPage(),
                'per_page'     => $requests->perPage(),
            ],
        ]);
    }

    public function approveWithdrawal(
        ApproveWithdrawalRequest $request,
        WithdrawalRequest $withdrawalRequest,
        WithdrawalService $withdrawalService
    ): JsonResponse {
        $this->authorize('approve', $withdrawalRequest);

        $result = $withdrawalService->approve(
            withdrawalRequest: $withdrawalRequest,
            admin: $request->user(),
            note: $request->validated('note')
        );

        return response()->json([
            'message' => 'تم اعتماد طلب السحب.',
            'data'    => new WithdrawalRequestResource($result),
        ]);
    }

    public function rejectWithdrawal(
        RejectWithdrawalRequest $request,
        WithdrawalRequest $withdrawalRequest,
        WithdrawalService $withdrawalService
    ): JsonResponse {
        $this->authorize('reject', $withdrawalRequest);

        $result = $withdrawalService->reject(
            withdrawalRequest: $withdrawalRequest,
            admin: $request->user(),
            reason: $request->validated('reason'),
            note: $request->validated('note')
        );

        return response()->json([
            'message' => 'تم رفض طلب السحب وإعادة الرصيد.',
            'data'    => new WithdrawalRequestResource($result),
        ]);
    }

    public function payWithdrawal(
        PayWithdrawalRequest $request,
        WithdrawalRequest $withdrawalRequest,
        WithdrawalService $withdrawalService
    ): JsonResponse {
        $this->authorize('pay', $withdrawalRequest);

        $result = $withdrawalService->markAsPaid(
            withdrawalRequest: $withdrawalRequest,
            admin: $request->user(),
            payoutReference: $request->validated('payout_reference'),
            note: $request->validated('note')
        );

        return response()->json([
            'message' => 'تم تحديث طلب السحب إلى مدفوع.',
            'data'    => new WithdrawalRequestResource($result),
        ]);
    }
}
