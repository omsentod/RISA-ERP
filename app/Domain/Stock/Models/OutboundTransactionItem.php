<?php

namespace App\Domain\Stock\Models;

use App\Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundTransactionItem extends Model
{
    protected $fillable = [
        'outbound_transaction_id',
        'product_id',
        'quantity',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'scanned_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(OutboundTransaction::class, 'outbound_transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
