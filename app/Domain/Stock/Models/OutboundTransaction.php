<?php

namespace App\Domain\Stock\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OutboundTransaction extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'doc_no',
        'doc_date',
        'destination',
        'notes',
        'status',
        'started_at',
        'completed_at',
        'total_qty',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'doc_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_qty' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OutboundTransactionItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function recalculateTotalQty(): void
    {
        $this->total_qty = (int) $this->items()->sum('quantity');
        $this->saveQuietly();
    }
}
