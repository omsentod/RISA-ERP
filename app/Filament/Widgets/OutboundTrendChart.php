<?php

namespace App\Filament\Widgets;

use App\Domain\Shared\Enums\DateRangePreset;
use App\Domain\Stock\Models\OutboundTransaction;
use App\Domain\Stock\Models\OutboundTransactionItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class OutboundTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Trend Barang Keluar';

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public ?array $filters = null;

    protected function getData(): array
    {
        [$from, $until] = $this->resolveRange();
        $granularity = $this->pickGranularity($from, $until);

        $format = match ($granularity) {
            'hour' => '%Y-%m-%d %H:00',
            'day' => '%Y-%m-%d',
            'month' => '%Y-%m',
        };

        // MySQL DATE_FORMAT; sqlite di test pakai strftime — di production selalu MySQL.
        $driver = DB::connection()->getDriverName();
        $bucketExpr = $driver === 'sqlite'
            ? "strftime('" . str_replace(['%Y', '%m', '%d', '%H'], ['%Y', '%m', '%d', '%H'], $format) . "', scanned_at)"
            : "DATE_FORMAT(scanned_at, '{$format}')";

        $rows = OutboundTransactionItem::query()
            ->selectRaw("$bucketExpr as bucket, SUM(quantity) as qty")
            ->whereBetween('scanned_at', [$from, $until])
            ->whereHas('transaction', fn (Builder $q) => $q->where('status', OutboundTransaction::STATUS_COMPLETED))
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('qty', 'bucket');

        // Fill gap: bikin semua bucket dalam range walau data 0
        $labels = [];
        $values = [];
        $cursor = $from->copy();
        while ($cursor->lte($until)) {
            $key = match ($granularity) {
                'hour' => $cursor->format('Y-m-d H:00'),
                'day' => $cursor->format('Y-m-d'),
                'month' => $cursor->format('Y-m'),
            };
            $labels[] = match ($granularity) {
                'hour' => $cursor->format('d M H:i'),
                'day' => $cursor->translatedFormat('d M'),
                'month' => $cursor->translatedFormat('M Y'),
            };
            $values[] = (int) ($rows[$key] ?? 0);

            $cursor = match ($granularity) {
                'hour' => $cursor->addHour(),
                'day' => $cursor->addDay(),
                'month' => $cursor->addMonthNoOverflow(),
            };
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Qty Keluar',
                    'data' => $values,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.35,
                    'pointRadius' => 3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    public function getDescription(): ?string
    {
        [$from, $until] = $this->resolveRange();
        $g = $this->pickGranularity($from, $until);
        $granularityLabel = match ($g) {
            'hour' => 'per jam',
            'day' => 'per hari',
            'month' => 'per bulan',
        };

        return $granularityLabel . ' · ' . $from->translatedFormat('d M Y') . ' – ' . $until->translatedFormat('d M Y');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }

    protected function resolveRange(): array
    {
        $preset = DateRangePreset::tryFrom($this->filters['preset'] ?? '') ?? DateRangePreset::ThisMonth;

        return $preset->range($this->filters['from'] ?? null, $this->filters['until'] ?? null);
    }

    /**
     * Auto-pick bucket granularity berdasarkan panjang range.
     * ≤ 2 hari → per jam, ≤ 90 hari → per hari, > 90 hari → per bulan.
     */
    protected function pickGranularity(Carbon $from, Carbon $until): string
    {
        $days = $from->diffInDays($until) + 1;

        return match (true) {
            $days <= 2 => 'hour',
            $days <= 90 => 'day',
            default => 'month',
        };
    }
}
