<?php

namespace App\Filament\Pages;

use App\Domain\Shared\Enums\DateRangePreset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('preset')
                    ->label('Periode Laporan')
                    ->options(DateRangePreset::options())
                    ->default(DateRangePreset::ThisMonth->value)
                    ->live()
                    ->selectablePlaceholder(false),
                Forms\Components\DatePicker::make('from')
                    ->label('Dari tanggal')
                    ->native(false)
                    ->visible(fn (Get $get) => $get('preset') === DateRangePreset::Custom->value),
                Forms\Components\DatePicker::make('until')
                    ->label('Sampai tanggal')
                    ->native(false)
                    ->visible(fn (Get $get) => $get('preset') === DateRangePreset::Custom->value)
                    ->afterOrEqual('from'),
            ])
            ->columns(3);
    }
}
