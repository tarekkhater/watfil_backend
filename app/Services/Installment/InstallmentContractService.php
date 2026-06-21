<?php

namespace App\Services\Installment;

use App\Models\CompanyProductInstallmentPlan;
use App\Models\InstallmentContract;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPenalty;
use App\Models\InstallmentSchedule;
use App\Models\Order;
use App\Services\Finance\WalletPostingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InstallmentContractService
{
    public function __construct(
        private readonly WalletPostingService $walletPostingService
    ) {
    }

    public function createFromOrder(Order $order, Model $actor): InstallmentContract
    {
        if ($order->payment_type !== 'installment') {
            throw ValidationException::withMessages([
                'payment_type' => ['الطلب ليس بطريقة تقسيط.'],
            ]);
        }

        if (! $order->installment_plan_id) {
            throw ValidationException::withMessages([
                'installment_plan_id' => ['خطة التقسيط غير محددة على الطلب.'],
            ]);
        }

        $idempotencyKey = "order:{$order->id}:installment_contract";

        $existing = InstallmentContract::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return $existing->load(['schedule.penalties', 'payments']);
        }

        return DB::transaction(function () use ($order, $actor, $idempotencyKey): InstallmentContract {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            $plan = CompanyProductInstallmentPlan::query()
                ->with('companyProduct')
                ->findOrFail($order->installment_plan_id);

            $snapshot = [
                'company_product_installment_plan_id' => $plan->id,
                'company_product_id'                  => $plan->company_product_id,
                'product_name'                        => $plan->companyProduct?->name,
                'months'                              => (int) $plan->months,
                'down_payment'                        => (float) $plan->down_payment,
                'installment_amount'                  => (float) $plan->installment_amount,
            ];

            $principal = round(
                $snapshot['down_payment'] + ($snapshot['months'] * $snapshot['installment_amount']),
                2
            );

            $contract = InstallmentContract::create([
                'order_id'                            => $order->id,
                'company_id'                          => $order->company_id,
                'customer_id'                         => $order->customer_id,
                'company_product_installment_plan_id' => $plan->id,
                'plan_snapshot'                       => $snapshot,
                'principal_amount'                    => $principal,
                'down_payment_amount'               => $snapshot['down_payment'],
                'months'                              => $snapshot['months'],
                'installment_amount'                  => $snapshot['installment_amount'],
                'paid_amount'                         => 0,
                'outstanding_amount'                  => $principal,
                'status'                              => 'active',
                'started_at'                          => now(),
                'idempotency_key'                     => $idempotencyKey,
            ]);

            $this->generateSchedule($contract, now());

            return $contract->load(['schedule.penalties', 'payments']);
        });
    }

    /**
     * @param array{
     *     installment_schedule_id: int,
     *     amount: float,
     *     payment_method?: string,
     *     notes?: string|null,
     *     paid_at?: string|null
     * } $data
     */
    public function recordPayment(
        InstallmentContract $contract,
        array $data,
        Model $actor,
        ?string $idempotencyKey = null
    ): InstallmentPayment {
        if ($idempotencyKey) {
            $existing = InstallmentPayment::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing->load(['schedule', 'contract']);
            }
        }

        return DB::transaction(function () use ($contract, $data, $actor, $idempotencyKey): InstallmentPayment {
            $contract = InstallmentContract::query()->lockForUpdate()->findOrFail($contract->id);

            if (! in_array($contract->status, ['active', 'defaulted'], true)) {
                throw ValidationException::withMessages([
                    'contract' => ['لا يمكن تسجيل دفعات على عقد غير نشط.'],
                ]);
            }

            $schedule = InstallmentSchedule::query()
                ->where('installment_contract_id', $contract->id)
                ->whereKey($data['installment_schedule_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($schedule->status, ['paid', 'waived'], true)) {
                throw ValidationException::withMessages([
                    'installment_schedule_id' => ['هذا القسط مسدد بالكامل بالفعل.'],
                ]);
            }

            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['مبلغ الدفعة يجب أن يكون أكبر من صفر.'],
                ]);
            }

            $dueAmount = $schedule->amountDue();

            if ($amount > $dueAmount) {
                throw ValidationException::withMessages([
                    'amount' => ["المبلغ يتجاوز المتبقي على القسط ({$dueAmount})."],
                ]);
            }

            $paidAt = isset($data['paid_at'])
                ? Carbon::parse($data['paid_at'])
                : now();

            $payment = InstallmentPayment::create([
                'installment_contract_id'  => $contract->id,
                'installment_schedule_id'  => $schedule->id,
                'amount'                   => $amount,
                'payment_method'           => $data['payment_method'] ?? 'cash',
                'recorded_by_type'         => $actor->getMorphClass(),
                'recorded_by_id'           => $actor->getKey(),
                'idempotency_key'          => $idempotencyKey,
                'notes'                    => $data['notes'] ?? null,
                'paid_at'                  => $paidAt,
            ]);

            $newPaidAmount = round((float) $schedule->paid_amount + $amount, 2);
            $scheduleTotal = round((float) $schedule->amount + $schedule->penaltyTotal(), 2);

            $schedule->paid_amount = $newPaidAmount;
            $schedule->status = $newPaidAmount >= $scheduleTotal ? 'paid' : 'partial';
            $schedule->paid_at = $schedule->status === 'paid' ? $paidAt : $schedule->paid_at;
            $schedule->save();

            $contract->paid_amount = round((float) $contract->paid_amount + $amount, 2);
            $contract->outstanding_amount = round((float) $contract->principal_amount - (float) $contract->paid_amount, 2);
            $contract->save();

            $this->walletPostingService->post(
                company: $contract->company()->lockForUpdate()->firstOrFail(),
                direction: 'credit',
                amount: $amount,
                category: 'installment_collection',
                description: "تحصيل قسط #{$schedule->installment_number} — عقد #{$contract->id}",
                idempotencyKey: $idempotencyKey ? "{$idempotencyKey}:wallet" : "installment_payment:{$payment->id}:wallet",
                source: $payment,
                actor: $actor,
                meta: [
                    'installment_contract_id' => (string) $contract->id,
                    'installment_schedule_id' => (string) $schedule->id,
                ]
            );

            $this->refreshContractStatus($contract);

            return $payment->load(['schedule', 'contract']);
        });
    }

    public function processOverdue(): int
    {
        $graceDays = (int) config('installments.grace_period_days', 3);
        $penaltyAmount = (float) config('installments.penalty_amount', 50);
        $defaultThreshold = (int) config('installments.defaulted_overdue_count', 3);
        $cutoffDate = now()->startOfDay()->subDays($graceDays);
        $processed = 0;

        InstallmentSchedule::query()
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', $cutoffDate)
            ->whereHas('contract', fn ($query) => $query->whereIn('status', ['active', 'defaulted']))
            ->with(['contract', 'penalties'])
            ->orderBy('id')
            ->chunkById(100, function ($schedules) use ($penaltyAmount, $defaultThreshold, &$processed): void {
                foreach ($schedules as $schedule) {
                    DB::transaction(function () use ($schedule, $penaltyAmount, $defaultThreshold, &$processed): void {
                        $schedule = InstallmentSchedule::query()->lockForUpdate()->findOrFail($schedule->id);

                        if (! in_array($schedule->status, ['pending', 'partial'], true)) {
                            return;
                        }

                        if ($schedule->status !== 'overdue') {
                            $schedule->update(['status' => 'overdue']);
                            $processed++;
                        }

                        $hasPenalty = $schedule->penalties()
                            ->where('reason', 'late_payment')
                            ->exists();

                        if (! $hasPenalty && $penaltyAmount > 0) {
                            InstallmentPenalty::create([
                                'installment_contract_id' => $schedule->installment_contract_id,
                                'installment_schedule_id' => $schedule->id,
                                'amount'                    => $penaltyAmount,
                                'reason'                    => 'late_payment',
                                'applied_at'                => now(),
                            ]);
                        }

                        $contract = InstallmentContract::query()->lockForUpdate()->find($schedule->installment_contract_id);

                        if (! $contract) {
                            return;
                        }

                        $overdueCount = InstallmentSchedule::query()
                            ->where('installment_contract_id', $contract->id)
                            ->where('status', 'overdue')
                            ->count();

                        if ($overdueCount >= $defaultThreshold && $contract->status === 'active') {
                            $contract->update(['status' => 'defaulted']);
                        }
                    });
                }
            });

        return $processed;
    }

    public function resolvePlanForOrder(int $planId, int $companyId, int $productId): CompanyProductInstallmentPlan
    {
        $plan = CompanyProductInstallmentPlan::query()
            ->whereKey($planId)
            ->whereHas('companyProduct', fn ($query) => $query
                ->where('company_id', $companyId)
                ->where('id', $productId)
                ->where('is_active', true))
            ->first();

        if (! $plan) {
            throw ValidationException::withMessages([
                'installment_plan_id' => ['خطة التقسيط غير صالحة لهذا المنتج أو الشركة.'],
            ]);
        }

        return $plan;
    }

    public function calculateOrderTotal(CompanyProductInstallmentPlan $plan): float
    {
        return round(
            (float) $plan->down_payment + ((int) $plan->months * (float) $plan->installment_amount),
            2
        );
    }

    private function generateSchedule(InstallmentContract $contract, Carbon $startDate): void
    {
        InstallmentSchedule::create([
            'installment_contract_id' => $contract->id,
            'installment_number'      => 0,
            'type'                    => 'down_payment',
            'due_date'                => $startDate->toDateString(),
            'amount'                  => $contract->down_payment_amount,
            'status'                  => 'pending',
        ]);

        for ($i = 1; $i <= $contract->months; $i++) {
            InstallmentSchedule::create([
                'installment_contract_id' => $contract->id,
                'installment_number'      => $i,
                'type'                    => 'installment',
                'due_date'                => $startDate->copy()->addMonths($i)->toDateString(),
                'amount'                  => $contract->installment_amount,
                'status'                  => 'pending',
            ]);
        }
    }

    private function refreshContractStatus(InstallmentContract $contract): void
    {
        $contract->refresh();

        $openSchedules = InstallmentSchedule::query()
            ->where('installment_contract_id', $contract->id)
            ->whereNotIn('status', ['paid', 'waived'])
            ->count();

        if ($openSchedules === 0) {
            $contract->update([
                'status'              => 'completed',
                'completed_at'        => now(),
                'outstanding_amount'  => 0,
            ]);
        }
    }
}
