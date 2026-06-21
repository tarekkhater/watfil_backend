<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'order_id'            => $this->order_id,
            'status'              => $this->status,
            'principal_amount'    => (float) $this->principal_amount,
            'down_payment_amount' => (float) $this->down_payment_amount,
            'months'              => $this->months,
            'installment_amount'  => (float) $this->installment_amount,
            'paid_amount'         => (float) $this->paid_amount,
            'outstanding_amount'  => (float) $this->outstanding_amount,
            'plan_snapshot'       => $this->plan_snapshot,
            'started_at'          => $this->started_at?->toDateTimeString(),
            'completed_at'        => $this->completed_at?->toDateTimeString(),
            'company'             => $this->whenLoaded('company', fn () => [
                'id'   => $this->company->id,
                'name' => $this->company->name,
            ]),
            'customer'            => new CustomerResource($this->whenLoaded('customer')),
            'order'               => $this->whenLoaded('order', fn () => [
                'id'           => $this->order->id,
                'status'       => $this->order->status,
                'payment_type' => $this->order->payment_type,
            ]),
            'schedule'            => InstallmentScheduleResource::collection($this->whenLoaded('schedule')),
            'payments'            => InstallmentPaymentResource::collection($this->whenLoaded('payments')),
            'created_at'          => $this->created_at?->toDateTimeString(),
            'updated_at'          => $this->updated_at?->toDateTimeString(),
        ];
    }
}
