<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransactionMeta extends Model
{
    protected $table = 'wallet_transaction_meta';

    protected $fillable = [
        'wallet_transaction_id',
        'meta_key',
        'meta_value',
    ];

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
