<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Company;
use App\Models\CompanyProduct;
use App\Models\Order;
use App\Models\SupplierProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with('company.governorate')
            ->latest()
            ->paginate(15);

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
        $company = Company::findOrFail($request->company_id);

        if (! $company->is_active) {
            return response()->json(['message' => 'هذه الشركة غير متاحة حاليًا'], 422);
        }

        $productData = $this->resolveProduct($request->product_type, $request->product_id, $company);

        if (! $productData) {
            return response()->json(['message' => 'المنتج غير متاح في متجر هذه الشركة'], 422);
        }

        $quantity   = $request->integer('quantity', 1);
        $unitPrice  = $productData['price'];
        $totalPrice = round((float) $unitPrice * $quantity, 2);

        $order = $request->user()->orders()->create([
            'company_id'       => $company->id,
            'product_type'     => $request->product_type,
            'product_id'       => $request->product_id,
            'quantity'         => $quantity,
            'unit_price'       => $unitPrice,
            'total_price'      => $totalPrice,
            'delivery_address' => $request->delivery_address,
            'notes'            => $request->notes,
            'status'           => Order::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'تم إنشاء الطلب بنجاح',
            'data'    => new OrderResource($order->load('company.governorate')),
        ], 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        return response()->json([
            'data' => new OrderResource($order->load('company.governorate')),
        ]);
    }

    private function resolveProduct(string $type, int $productId, Company $company): ?array
    {
        if ($type === Order::TYPE_COMPANY_PRODUCT) {
            $product = CompanyProduct::where('id', $productId)
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->first();

            if (! $product) {
                return null;
            }

            return ['price' => $product->cash_price];
        }

        if ($type === Order::TYPE_SUPPLIER_PRODUCT) {
            $product = SupplierProduct::where('id', $productId)
                ->where('is_active', true)
                ->first();

            if (! $product) {
                return null;
            }

            $inCatalog = $company->catalogProducts()
                ->where('supplier_products.id', $productId)
                ->exists();

            if (! $inCatalog) {
                return null;
            }

            return ['price' => $product->cash_price];
        }

        return null;
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        if ($order->customer_id !== $request->user()->id) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }
    }
}
