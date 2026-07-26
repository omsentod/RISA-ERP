<?php

namespace App\Domain\Shared\Enums;

use Illuminate\Support\Carbon;

enum DateRangePreset: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case Last7Days = 'last_7_days';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case ThisYear = 'this_year';
    case LastYear = 'last_year';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Hari ini',
            self::Yesterday => 'Kemarin',
            self::Last7Days => '7 hari terakhir',
            self::ThisMonth => 'Bulan ini',
            self::LastMonth => 'Bulan lalu',
            self::ThisYear => 'Tahun ini',
            self::LastYear => 'Tahun lalu',
            self::Custom => 'Kustom (pilih tanggal)',
        };
    }

    /**
     * Return list of [value => label] for Select field options.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }

    /**
     * Return [Carbon $from, Carbon $until] for this preset.
     * For Custom, caller must supply $customFrom/$customUntil.
     */
    public function range(?string $customFrom = null, ?string $customUntil = null): array
    {
        return match ($this) {
            self::Today => [now()->startOfDay(), now()->endOfDay()],
            self::Yesterday => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            self::Last7Days => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            self::ThisMonth => [now()->startOfMonth(), now()->endOfMonth()],
            self::LastMonth => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            self::ThisYear => [now()->startOfYear(), now()->endOfYear()],
            self::LastYear => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            self::Custom => [
                $customFrom ? Carbon::parse($customFrom)->startOfDay() : now()->subMonth()->startOfDay(),
                $customUntil ? Carbon::parse($customUntil)->endOfDay() : now()->endOfDay(),
            ],
        };
    }

    /**
     * Human-readable label for a resolved range, e.g. "1 Jul 2026 – 31 Jul 2026".
     */
    public function humanRange(?string $customFrom = null, ?string $customUntil = null): string
    {
        [$from, $until] = $this->range($customFrom, $customUntil);

        return $from->translatedFormat('d M Y') . ' – ' . $until->translatedFormat('d M Y');
    }
}
