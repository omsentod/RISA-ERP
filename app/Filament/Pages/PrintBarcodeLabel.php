<?php

namespace App\Filament\Pages;

use App\Domain\Product\Actions\GenerateBarcode;
use App\Domain\Product\Models\Product;
use Filament\Pages\Page;

class PrintBarcodeLabel extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'print-barcode-label';

    protected static string $view = 'filament.pages.print-barcode-label';

    protected static ?string $title = 'Cetak Label Barcode';

    public array $productIds = [];

    public function mount(): void
    {
        $raw = request()->query('ids', '');
        $this->productIds = collect(explode(',', $raw))
            ->filter(fn ($v) => is_numeric(trim($v)))
            ->map(fn ($v) => (int) trim($v))
            ->unique()
            ->values()
            ->all();
    }

    public function getProducts()
    {
        if (empty($this->productIds)) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $this->productIds)
            ->orderByRaw('FIELD(id,' . implode(',', $this->productIds) . ')')
            ->get(['id', 'code', 'name']);
    }

    public function barcodeSvg(string $code): string
    {
        return app(GenerateBarcode::class)->svg($code, widthFactor: 2, height: 50);
    }
}
