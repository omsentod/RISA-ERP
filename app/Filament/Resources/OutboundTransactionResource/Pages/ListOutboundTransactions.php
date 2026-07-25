<?php

namespace App\Filament\Resources\OutboundTransactionResource\Pages;

use App\Domain\Stock\Actions\StartOutboundSession;
use App\Filament\Concerns\HasSelectionToggle;
use App\Filament\Pages\ScanOutbound;
use App\Filament\Resources\OutboundTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOutboundTransactions extends ListRecords
{
    use HasSelectionToggle;

    protected static string $resource = OutboundTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getSelectionToggleAction(),
            Actions\Action::make('startSession')
                ->label('Scan Produk')
                ->icon('heroicon-o-qr-code')
                ->color('primary')
                ->action(function () {
                    $transaction = app(StartOutboundSession::class)->handle();

                    return redirect(ScanOutbound::getUrl(['transaction' => $transaction->id]));
                }),
        ];
    }
}
