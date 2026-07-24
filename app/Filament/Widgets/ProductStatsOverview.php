<?php

namespace App\Filament\Widgets;

use App\Domain\Product\Models\Product;
use App\Domain\Registration\Models\Registration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $totalNie = Registration::count();
        $nieExpiringSoon = Registration::query()
            ->whereDate('expired_at', '>=', now())
            ->whereDate('expired_at', '<=', now()->addMonths(3))
            ->count();
        $nieExpired = Registration::whereDate('expired_at', '<', now())->count();
        $productsWithoutNie = Product::whereNull('registration_id')->count();

        return [
            Stat::make('Total Produk', number_format($totalProducts))
                ->description('Semua SKU aktif')
                ->descriptionIcon('heroicon-m-cube', 'before')
                ->color('success'),

            Stat::make('Total NIE Aktif', number_format($totalNie))
                ->description('Nomor Izin Edar terdaftar')
                ->descriptionIcon('heroicon-m-identification', 'before')
                ->color('info'),

            Stat::make('NIE Segera Expired', number_format($nieExpiringSoon))
                ->description('Dalam 3 bulan ke depan')
                ->descriptionIcon('heroicon-m-clock', 'before')
                ->color($nieExpiringSoon > 0 ? 'warning' : 'gray'),

            Stat::make('NIE Sudah Expired', number_format($nieExpired))
                ->description($nieExpired > 0 ? 'Perlu perpanjangan segera' : 'Semua NIE masih valid')
                ->descriptionIcon('heroicon-m-exclamation-triangle', 'before')
                ->color($nieExpired > 0 ? 'danger' : 'success'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
