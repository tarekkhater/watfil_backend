<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'company_id'      => $this->company_id,
            'direction'       => $this->direction,
            'category'        => $this->category,
            'amount'          => $this->amount,
            'balance_before'  => $this->balance_before,
            'balance_after'   => $this->balance_after,
            'idempotency_key' => $this->idempotency_key,
            'description'     => $this->description,
            'meta'            => $this->whenLoaded('meta', function (): array {
                return $this->meta->mapWithKeys(
                    fn ($item): array => [$item->meta_key => $item->meta_value]
                )->all();
            }, []),
            'created_at'      => $this->created_at?->toDateTimeString(),
        ];
    }
}
