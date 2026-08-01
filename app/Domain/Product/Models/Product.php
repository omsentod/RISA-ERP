<?php

namespace App\Domain\Product\Models;

use App\Domain\Registration\Models\Registration;
use App\Domain\Stock\Models\OutboundTransactionItem;
use App\Models\User;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_category_id',
        'registration_id',
        'code',
        'name',
        'specification',
        'default_lot',
        'default_quantity',
        'product_group_code',
        'description',
        'is_published',
        'published_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'default_quantity' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function outboundItems(): HasMany
    {
        return $this->hasMany(OutboundTransactionItem::class);
    }

    protected static function newFactory()
    {
        return ProductFactory::new();
    }
}
