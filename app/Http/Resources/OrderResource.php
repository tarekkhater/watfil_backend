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
