<?php

namespace App\Domain\Stock\Exceptions;

use App\Domain\Product\Models\Product;
use Illuminate\Support\Collection;
use RuntimeException;

class AmbiguousScanException extends RuntimeException
{
    /**
     * @param Collection<int, Product> $candidates
     */
    public function __construct(
        public readonly Collection $candidates,
        public readonly string $scannedCode,
        public readonly int $qtyToAdd,
    ) {
        parent::__construct("Kode \"{$scannedCode}\" dimiliki {$candidates->count()} produk. Pilih salah satu.");
    }
}
