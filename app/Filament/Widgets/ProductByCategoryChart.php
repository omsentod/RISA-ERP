<?php

namespace App\Filament\Widgets;

use App\Domain\Product\Models\ProductCategory;
use Filament\Widgets\ChartWidget;

class ProductByCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Produk per Kategori';

    protected static ?string $description = 'Distribusi produk antara LOCKING dan NON LOCKING';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $data = ProductCategory::query()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Produk',
                    'data' => $data->pluck('products_count')->all(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',   // biru — LOCKING
                        'rgba(107, 114, 128, 0.8)',  // abu-abu — NON LOCKING
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(107, 114, 128)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $data->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '65%',
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }
}
