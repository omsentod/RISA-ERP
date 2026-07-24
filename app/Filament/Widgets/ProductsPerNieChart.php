<?php

namespace App\Filament\Widgets;

use App\Domain\Registration\Models\Registration;
use Filament\Widgets\ChartWidget;

class ProductsPerNieChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Produk per NIE';

    protected static ?string $description = 'Top 10 NIE dengan produk terbanyak';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $data = Registration::query()
            ->withCount('products')
            ->orderByDesc('products_count')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Produk',
                    'data' => $data->pluck('products_count')->all(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.7)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $data->pluck('nie_number')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
