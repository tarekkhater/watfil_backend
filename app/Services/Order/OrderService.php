<?php

namespace App\Services\Order;

use App\Models\Company;
use App\Models\CompanyProduct;
use App\Models\Customer;
use App\Models\CustomerCompanyLink;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSource;
use App\Models\OrderStatusHistory;
use App\Services\Finance\CommissionService;
use App\Services\Installment\InstallmentContractService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public const STATUSES = ['pending', 'processing', 'completed', 'cancelled'];

    /** @var array<string, list<string>> */
    private const ALLOWED_TRANSITIONS = [
        'pending'    => ['processing', 'cancelled'],
        'processing' => ['completed', 'cancelled'],
        'completed'  => [],
        'cancelled'  => [],
    ];

    public function __construct(
        private readonly CommissionService $commissionService,
        private readonly InstallmentContractService $installmentContractService
    ) {
    }

    /**
     * @param array{
     *     company_id: int,
     *     customer_id: int,
     *     payment_type: string,
     *     installment_plan_id?: int|null,
     *     items: list<array{company_product_id: int, quantity: int}>,
     *     discount?: float,
     *     notes?: string|null,
     *     governorate_id?: int|null,
     *     source?: array{channel: string, reference_type?: string|null, reference_id?: int|null, metadata?: array|null}|null
     * } $data
     */
    public function create(array $data, Model $actor, ?string $idempotencyKey = null): Order
    {
        if ($idempotencyKey) {
            $existing = Order::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing->load(['items', 'source', 'statusHistory', 'company', 'customer.profile']);
            }
        }

        return DB::transaction(function () use ($data, $actor, $idempotencyKey): Order {
            $company = Company::query()->lockForUpdate()->findOrFail($data['company_id']);

            if (! $company->is_active) {
                throw ValidationException::withMessages([
                    'company_id' => ['الشركة غير نشطة حالياً.'],
                ]);
            }

            $this->assertCustomerLinkedToCompany((int) $data['customer_id'], $company->id);

            $products = $this->resolveOrderProducts($data['items'], $company->id);

            if ($data['payment_type'] === 'installment') {
                $this->assertInstallmentOrderRules($data);
            }

            $lineItems = $this->buildLineItems($data['items'], $products, $data['payment_type'] ?? 'cash', $data);
            $subtotal = round($lineItems->sum('line_total'), 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);

            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount' => ['قيمة الخصم لا يمكن أن تتجاوز إجمالي الطلب.'],
                ]);
            }

            $order = Order::create([
                'company_id'       => $company->id,
                'customer_id'      => $data['customer_id'],
                'status'           => 'pending',
                'payment_type'        => $data['payment_type'],
                'installment_plan_id' => $data['installment_plan_id'] ?? null,
                'subtotal'            => $subtotal,
                'discount'         => $discount,
                'total_amount'     => round($subtotal - $discount, 2),
                'governorate_id'   => $data['governorate_id'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'idempotency_key'  => $idempotencyKey,
            ]);

            foreach ($lineItems as $item) {
                OrderItem::create([
                    'order_id'           => $order->id,
                    'company_product_id' => $item['company_product_id'],
                    'product_name'       => $item['product_name'],
                    'quantity'           => $item['quantity'],
                    'unit_price'         => $item['unit_price'],
                    'line_total'         => $item['line_total'],
                ]);
            }

            if (! empty($data['source']['channel'] ?? null)) {
                OrderSource::create([
                    'order_id'       => $order->id,
                    'channel'        => $data['source']['channel'],
                    'reference_type' => $data['source']['reference_type'] ?? null,
                    'reference_id'   => $data['source']['reference_id'] ?? null,
                    'metadata'       => $data['source']['metadata'] ?? null,
                ]);
            } else {
                OrderSource::create([
                    'order_id' => $order->id,
                    'channel'  => 'direct',
                ]);
            }

            $this->recordStatusHistory($order, null, 'pending', $actor, 'تم إنشاء الطلب');

            return $order->load(['items', 'source', 'statusHistory', 'company', 'customer.profile']);
        });
    }

    public function transitionStatus(
        Order $order,
        string $toStatus,
        Model $actor,
        ?string $note = null,
        ?string $cancellationReason = null
    ): Order {
        if (! in_array($toStatus, self::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => ['حالة الطلب غير صالحة.'],
            ]);
        }

        return DB::transaction(function () use ($order, $toStatus, $actor, $note, $cancellationReason): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $fromStatus = $order->status;

            if (! in_array($toStatus, self::ALLOWED_TRANSITIONS[$fromStatus] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => ["لا يمكن الانتقال من حالة {$fromStatus} إلى {$toStatus}."],
                ]);
            }

            if ($toStatus === 'cancelled' && blank($cancellationReason)) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => ['سبب الإلغاء مطلوب عند إلغاء الطلب.'],
                ]);
            }

            $updates = ['status' => $toStatus];

            if ($toStatus === 'completed') {
                $updates['completed_at'] = now();
            }

            if ($toStatus === 'cancelled') {
                $updates['cancelled_at'] = now();
                $updates['cancellation_reason'] = $cancellationReason;
            }

            $order->update($updates);

            $this->recordStatusHistory($order, $fromStatus, $toStatus, $actor, $note);

            if ($toStatus === 'completed') {
                $order->load(['source', 'company']);

                $this->commissionService->apply(
                    company: $order->company,
                    trigger: 'order_completed',
                    grossAmount: (float) $order->total_amount,
                    source: $order,
                    idempotencyKey: "order:{$order->id}:commission",
                    actor: $actor,
                    sourceChannel: $order->source?->channel
                );

                if ($order->payment_type === 'installment') {
                    $this->installmentContractService->createFromOrder($order, $actor);
                }
            }

            return $order->fresh([
                'items',
                'source',
                'statusHistory',
                'company',
                'customer.profile',
                'governorate',
                'installmentContract.schedule',
            ]);
        });
    }

    /**
     * @param list<array{company_product_id: int, quantity: int}> $items
     * @return Collection<int, CompanyProduct>
     */
    private function resolveOrderProducts(array $items, int $companyId): Collection
    {
        $productIds = collect($items)->pluck('company_product_id')->unique()->values();

        $products = CompanyProduct::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'items' => ['أحد المنتجات غير تابع لهذه الشركة أو غير موجود.'],
            ]);
        }

        $inactive = $products->first(fn (CompanyProduct $product) => ! $product->is_active);

        if ($inactive) {
            throw ValidationException::withMessages([
                'items' => ["المنتج {$inactive->name} غير نشط حالياً."],
            ]);
        }

        return $products;
    }

    /**
     * @param list<array{company_product_id: int, quantity: int}> $items
     * @param Collection<int, CompanyProduct> $products
     * @return Collection<int, array{company_product_id: int, product_name: string, quantity: int, unit_price: float, line_total: float}>
     */
    /**
     * @param array<string, mixed> $data
     */
    private function buildLineItems(array $items, Collection $products, string $paymentType, array $data): Collection
    {
        if ($paymentType === 'installment') {
            $productId = (int) $items[0]['company_product_id'];
            $plan = $this->installmentContractService->resolvePlanForOrder(
                (int) $data['installment_plan_id'],
                (int) $data['company_id'],
                $productId
            );
            $product = $products->get($productId);
            $lineTotal = $this->installmentContractService->calculateOrderTotal($plan);

            return collect([[
                'company_product_id' => $product->id,
                'product_name'       => $product->name,
                'quantity'           => 1,
                'unit_price'         => $lineTotal,
                'line_total'         => $lineTotal,
            ]]);
        }

        return collect($items)->map(function (array $item) use ($products) {
            $product = $products->get($item['company_product_id']);
            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $product->cash_price;
            $lineTotal = round($unitPrice * $quantity, 2);

            return [
                'company_product_id' => $product->id,
                'product_name'       => $product->name,
                'quantity'           => $quantity,
                'unit_price'         => $unitPrice,
                'line_total'         => $lineTotal,
            ];
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertInstallmentOrderRules(array $data): void
    {
        if (blank($data['installment_plan_id'] ?? null)) {
            throw ValidationException::withMessages([
                'installment_plan_id' => ['خطة التقسيط مطلوبة عند اختيار الدفع بالتقسيط.'],
            ]);
        }

        if (count($data['items']) !== 1) {
            throw ValidationException::withMessages([
                'items' => ['طلب التقسيط يدعم منتجاً واحداً فقط في الوقت الحالي.'],
            ]);
        }

        if ((int) ($data['items'][0]['quantity'] ?? 0) !== 1) {
            throw ValidationException::withMessages([
                'items.0.quantity' => ['كمية منتج التقسيط يجب أن تكون 1.'],
            ]);
        }
    }

    private function assertCustomerLinkedToCompany(int $customerId, int $companyId): void
    {
        $isLinked = CustomerCompanyLink::query()
            ->where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->exists();

        if (! $isLinked) {
            throw ValidationException::withMessages([
                'customer_id' => ['العميل غير مرتبط بهذه الشركة.'],
            ]);
        }
    }

    private function recordStatusHistory(
        Order $order,
        ?string $fromStatus,
        string $toStatus,
        Model $actor,
        ?string $note = null
    ): void {
        OrderStatusHistory::create([
            'order_id'    => $order->id,
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
            'actor_type'  => $actor->getMorphClass(),
            'actor_id'    => $actor->getKey(),
            'note'        => $note,
            'created_at'  => now(),
        ]);
    }
}
