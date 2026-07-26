<?php

namespace App\Filament\Widgets;

use App\Domain\Product\Models\Product;
use App\Domain\Shared\Enums\DateRangePreset;
use App\Domain\Stock\Models\OutboundTransaction;
use App\Domain\Stock\Models\OutboundTransactionItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Reactive;

class OutboundStatsOverview extends BaseWidget
{
    protected static ?int $sort = 10;

    #[Reactive]
    public ?array $filters = null;

    protected function getStats(): array
    {
        [$from, $until] = $this->resolveRange();

        $items = OutboundTransactionItem::query()
            ->whereBetween('scanned_at', [$from, $until])
            ->whereHas('transaction', fn (Builder $q) => $q->where('status', OutboundTransaction::STATUS_COMPLETED));

        $totalUnit = (int) (clone $items)->sum('quantity');
        $totalTrx = OutboundTransaction::query()
            ->where('status', OutboundTransaction::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$from, $until])
            ->count();

        $topProduct = Product::query()
            ->select('products.*')
            ->withSum(
                ['outboundItems as qty_in_range' => fn (Builder $q) => $q
                    ->whereBetween('scanned_at', [$from, $until])
                    ->whereHas('transaction', fn (Builder $qq) => $qq->where('status', OutboundTransaction::STATUS_COMPLETED)),
                ],
                'quantity'
            )
            ->orderByDesc('qty_in_range')
            ->first();

        $topLabel = $topProduct && $topProduct->qty_in_range > 0
            ? $topProduct->code . ' (' . $topProduct->qty_in_range . ' unit)'
            : '—';

        $periodLabel = $this->getPeriodLabel();

        return [
            Stat::make('Total Unit Keluar', number_format($totalUnit))
                ->description($periodLabel)
                ->descriptionIcon('heroicon-m-arrow-up-tray', 'before')
                ->color('primary'),

            Stat::make('Jumlah Transaksi', number_format($totalTrx))
                ->description('Surat Jalan diselesaikan · ' . $periodLabel)
                ->descriptionIcon('heroicon-m-document-check', 'before')
                ->color('info'),

            Stat::make('Produk Paling Laku', $topLabel)
                ->description($topProduct?->name ?? 'Belum ada data')
                ->descriptionIcon('heroicon-m-trophy', 'before')
                ->color($topProduct ? 'success' : 'gray'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected function resolveRange(): array
    {
        $preset = DateRangePreset::tryFrom($this->filters['preset'] ?? '') ?? DateRangePreset::ThisMonth;

        return $preset->range($this->filters['from'] ?? null, $this->filters['until'] ?? null);
    }

    protected function getPeriodLabel(): string
    {
        $preset = DateRangePreset::tryFrom($this->filters['preset'] ?? '') ?? DateRangePreset::ThisMonth;

        return $preset === DateRangePreset::Custom
            ? $preset->humanRange($this->filters['from'] ?? null, $this->filters['until'] ?? null)
            : $preset->label();
    }
}
