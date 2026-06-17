<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreOrderRequest;
use App\Http\Requests\Company\UpdateOrderStatusRequest;
use App\Http\Requests\Order\ListOrdersRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {
    }

    public function index(ListOrdersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $company = $request->user();

        $orders = Order::query()
            ->with(['customer.profile', 'source', 'items'])
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
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'total'        => $orders->total(),
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
            ],
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        $company = $request->user();
        $data = $request->validated();
        $data['company_id'] = $company->id;

        $order = $this->orderService->create(
            $data,
            $company,
            $data['idempotency_key'] ?? null
        );

        return response()->json([
            'message' => 'تم إنشاء الطلب بنجاح',
            'data'    => new OrderResource($order),
        ], 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['customer.profile', 'items', 'source', 'statusHistory', 'governorate']);

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $this->authorize('updateStatus', $order);

        $data = $request->validated();

        $order = $this->orderService->transitionStatus(
            $order,
            $data['status'],
            $request->user(),
            $data['note'] ?? null,
            $data['cancellation_reason'] ?? null
        );

        return response()->json([
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'data'    => new OrderResource($order),
        ]);
    }
}
