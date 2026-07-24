<?php

namespace App\Domain\Registration\Models;

use App\Domain\Product\Models\Product;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nie_number',
        'issuer',
        'issued_at',
        'expired_at',
        'attachment_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expired_at' => 'date',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function isExpired(): bool
    {
        return $this->expired_at !== null && $this->expired_at->isPast();
    }

    public function isExpiringSoon(int $months = 3): bool
    {
        return $this->expired_at !== null
            && !$this->isExpired()
            && $this->expired_at->diffInMonths(now()) <= $months;
    }

    protected static function newFactory()
    {
        return RegistrationFactory::new();
    }
}
