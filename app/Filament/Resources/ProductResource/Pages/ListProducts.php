<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Pages\ImportProduct;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-document-arrow-up')
                ->color('warning')
                ->url(ImportProduct::getUrl()),
            Actions\CreateAction::make(),
        ];
    }
}
