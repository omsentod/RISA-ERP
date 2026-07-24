<?php

namespace App\Filament\Widgets;

use App\Domain\Registration\Models\Registration;
use App\Filament\Resources\RegistrationResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class NieExpiringSoonTable extends BaseWidget
{
    protected static ?string $heading = 'NIE yang Perlu Diperpanjang';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Registration::query()
                    ->withCount('products')
                    ->where(function (Builder $q) {
                        $q->whereDate('expired_at', '<=', now()->addMonths(6))
                            ->orWhereNull('expired_at');
                    })
                    ->orderByRaw('expired_at IS NULL, expired_at ASC')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nie_number')
                    ->label('Nomor NIE')
                    ->weight('medium')
                    ->copyable(),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('Expired')
                    ->date('d M Y')
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record?->expired_at === null => 'gray',
                        $record?->isExpired() => 'danger',
                        $record?->isExpiringSoon() => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) {
                            return 'Belum diisi';
                        }
                        $label = $state->format('d M Y');
                        if ($record?->isExpired()) {
                            return $label . ' — EXPIRED';
                        }
                        if ($record?->isExpiringSoon()) {
                            return $label . ' — Segera Expired';
                        }

                        return $label;
                    }),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Jumlah Produk')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('issuer')
                    ->label('Penerbit')
                    ->badge()
                    ->color('gray'),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => RegistrationResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
