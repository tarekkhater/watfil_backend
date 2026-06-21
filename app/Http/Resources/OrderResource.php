<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'status'              => $this->status,
            'payment_type'        => $this->payment_type,
            'subtotal'            => (float) $this->subtotal,
            'discount'            => (float) $this->discount,
            'total_amount'        => (float) $this->total_amount,
            'notes'               => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'completed_at'        => $this->completed_at?->toDateTimeString(),
            'cancelled_at'        => $this->cancelled_at?->toDateTimeString(),
            'company'             => $this->whenLoaded('company', fn () => [
                'id'   => $this->company->id,
                'name' => $this->company->name,
            ]),
            'customer'            => new CustomerResource($this->whenLoaded('customer')),
            'governorate'         => $this->whenLoaded('governorate', fn () => [
                'id'      => $this->governorate->id,
                'name_ar' => $this->governorate->name_ar,
                'name_en' => $this->governorate->name_en,
            ]),
            'items'               => OrderItemResource::collection($this->whenLoaded('items')),
            'source'              => new OrderSourceResource($this->whenLoaded('source')),
            'status_history'      => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'created_at'          => $this->created_at?->toDateTimeString(),
            'updated_at'          => $this->updated_at?->toDateTimeString(),
        ];
    }
}
<?php

namespace App\Http\Resources;

use App\Models\CompanyProduct;
use App\Models\Order;
use App\Models\SupplierProduct;
use App\Support\PublicFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->resolveProduct();

        return [
            'id'               => $this->id,
            'product_type'     => $this->product_type,
            'product_id'       => $this->product_id,
            'quantity'         => $this->quantity,
            'unit_price'       => $this->unit_price,
            'total_price'      => $this->total_price,
            'delivery_address' => $this->delivery_address,
            'notes'            => $this->notes,
            'status'           => $this->status,
            'company'          => new PublicCompanyResource($this->whenLoaded('company')),
            'product'          => $product,
            'created_at'       => $this->created_at?->toDateTimeString(),
        ];
    }

    private function resolveProduct(): ?array
    {
        if ($this->product_type === Order::TYPE_COMPANY_PRODUCT) {
            $product = CompanyProduct::with('installmentPlans')->find($this->product_id);

            if (! $product) {
                return null;
            }

            return [
                'id'                => $product->id,
                'source'            => 'company',
                'name'              => $product->name,
                'description'       => $product->description,
                'image'             => PublicFile::url($product->image),
                'cash_price'        => $product->cash_price,
                'installment_plans' => CompanyProductInstallmentPlanResource::collection(
                    $product->installmentPlans
                )->resolve(),
            ];
        }

        $product = SupplierProduct::with(['supplier', 'installmentPlans'])->find($this->product_id);

        if (! $product) {
            return null;
        }

        return [
            'id'                => $product->id,
            'source'            => 'catalog',
            'name'              => $product->name,
            'description'       => $product->description,
            'image'             => PublicFile::url($product->image),
            'cash_price'        => $product->cash_price,
            'installment_plans' => CompanyProductInstallmentPlanResource::collection(
                $product->installmentPlans
            )->resolve(),
            'supplier'          => $product->supplier
                ? (new SupplierResource($product->supplier))->resolve()
                : null,
        ];
    }
}
