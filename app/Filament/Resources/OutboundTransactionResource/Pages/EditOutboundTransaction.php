<?php

namespace App\Filament\Resources\OutboundTransactionResource\Pages;

use App\Filament\Resources\OutboundTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOutboundTransaction extends EditRecord
{
    protected static string $resource = OutboundTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
