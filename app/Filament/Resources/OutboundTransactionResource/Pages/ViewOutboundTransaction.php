<?php

namespace App\Filament\Resources\OutboundTransactionResource\Pages;

use App\Domain\Stock\Actions\BuildPrintSuratJalanJs;
use App\Filament\Pages\ScanOutbound;
use App\Filament\Resources\OutboundTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOutboundTransaction extends ViewRecord
{
    protected static string $resource = OutboundTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('continueScan')
                ->label('Lanjut Scan')
                ->icon('heroicon-o-qr-code')
                ->color('warning')
                ->visible(fn () => $this->record->isDraft())
                ->url(fn () => ScanOutbound::getUrl(['transaction' => $this->record->id])),
            Actions\Action::make('printSuratJalan')
                ->label('Cetak Surat Jalan')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->visible(fn () => $this->record->isCompleted())
                ->action(function () {
                    $this->js(app(BuildPrintSuratJalanJs::class)->handle($this->record));
                }),
            Actions\EditAction::make()
                ->visible(fn () => !$this->record->isCancelled()),
        ];
    }
}
