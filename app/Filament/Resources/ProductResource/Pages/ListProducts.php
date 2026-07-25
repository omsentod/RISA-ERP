<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Domain\Product\Actions\BuildPrintBarcodeJs;
use App\Filament\Concerns\HasSelectionToggle;
use App\Filament\Pages\ImportProduct;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    use HasSelectionToggle;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getSelectionToggleAction(),
            Actions\CreateAction::make()->label('New Produk'),
            Actions\ActionGroup::make([
                Actions\Action::make('printAllFiltered')
                    ->label('Cetak Semua (Filter Aktif)')
                    ->icon('heroicon-o-printer')
                    ->requiresConfirmation()
                    ->modalHeading('Cetak Label untuk Semua Produk yang Sedang Difilter')
                    ->modalDescription('Akan trigger pop-up cetak untuk semua produk yang sesuai filter/pencarian saat ini. Maksimum 200 produk per batch.')
                    ->action(function () {
                        $ids = $this->getFilteredTableQuery()->limit(200)->pluck('id')->all();
                        if (empty($ids)) {
                            Notification::make()->title('Tidak ada produk untuk dicetak')->warning()->send();

                            return;
                        }

                        $this->js(app(BuildPrintBarcodeJs::class)->handle($ids));
                    }),
                Actions\Action::make('import')
                    ->label('Import Excel')
                    ->icon('heroicon-o-document-arrow-up')
                    ->url(ImportProduct::getUrl()),
            ])
                ->label('Lainnya')
                ->icon('heroicon-o-ellipsis-vertical')
                ->color('gray')
                ->button(),
        ];
    }
}
