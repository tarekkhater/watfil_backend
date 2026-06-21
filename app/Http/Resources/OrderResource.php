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
            'installment_plan'    => $this->when(
                $this->payment_type === 'installment',
                $this->installment_plan
            ),
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
