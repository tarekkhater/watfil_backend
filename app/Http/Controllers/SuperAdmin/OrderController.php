<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\ListOrdersRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function index(ListOrdersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::query()
            ->with(['company', 'customer.profile', 'source', 'items'])
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
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'total'        => $orders->total(),
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
            ],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['company', 'customer.profile', 'items', 'source', 'statusHistory', 'governorate']);

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }
}
