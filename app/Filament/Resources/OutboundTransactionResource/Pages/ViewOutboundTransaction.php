<?php

namespace App\Filament\Resources\OutboundTransactionResource\Pages;

use App\Domain\Stock\Actions\BuildPrintSuratJalanJs;
use App\Filament\Pages\ScanOutbound;
use App\Filament\Resources\OutboundTransactionResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOutboundTransaction extends ViewRecord
{
    protected static string $resource = OutboundTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(OutboundTransactionResource::getUrl('index')),
            Actions\Action::make('continueScan')
                ->label('Lanjut Scan')
                ->icon('heroicon-o-qr-code')
                ->color('warning')
                ->visible(fn () => $this->record->isDraft())
                ->url(fn () => ScanOutbound::getUrl(['transaction' => $this->record->id])),
            Actions\Action::make('reopenSession')
                ->label('Buka Kembali')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->isCompleted())
                ->requiresConfirmation()
                ->modalHeading('Buka Kembali Surat Jalan')
                ->modalDescription('SJ akan dikembalikan ke status Draft sehingga bisa diedit kembali. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Buka Kembali')
                ->action(function () {
                    $this->record->reopenSession();

                    Notification::make()
                        ->title('SJ dibuka kembali')
                        ->body("Surat Jalan {$this->record->doc_no} kembali ke status Draft.")
                        ->success()
                        ->send();

                    return redirect(ScanOutbound::getUrl(['transaction' => $this->record->id]));
                }),
            Actions\Action::make('editItems')
                ->label('Edit Item')
                ->icon('heroicon-o-pencil-square')
                ->color('info')
                ->visible(fn () => $this->record->isCompleted())
                ->url(fn () => OutboundTransactionResource::getUrl('edit-items', ['record' => $this->record->id])),
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
