<?php

namespace App\Filament\Resources\OutboundTransactionResource\Pages;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Models\OutboundTransaction;
use App\Domain\Stock\Models\OutboundTransactionItem;
use App\Filament\Resources\OutboundTransactionResource;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class EditOutboundItems extends Page
{
    protected static string $resource = OutboundTransactionResource::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.edit-outbound-items';

    protected static ?string $title = 'Edit Item Surat Jalan';

    public OutboundTransaction $transaction;

    public ?int $addProductId = null;

    public int $addQty = 1;

    public string $addLotNumber = '';

    public function mount(int $record): void
    {
        $this->transaction = OutboundTransaction::with('items.product')->findOrFail($record);

        if ($this->transaction->isCancelled()) {
            Notification::make()
                ->title('Transaksi sudah dibatalkan')
                ->warning()
                ->send();
            redirect(OutboundTransactionResource::getUrl('index'));
        }
    }

    public function incrementItemQty(int $itemId): void
    {
        $item = $this->transaction->items()->find($itemId);
        if (! $item) {
            return;
        }
        $item->increment('quantity');
        $this->transaction->recalculateTotalQty();
        $this->refreshTransaction();
    }

    public function decrementItemQty(int $itemId): void
    {
        $item = $this->transaction->items()->find($itemId);
        if (! $item) {
            return;
        }
        if ($item->quantity <= 1) {
            $item->delete();
        } else {
            $item->decrement('quantity');
        }
        $this->transaction->recalculateTotalQty();
        $this->refreshTransaction();
    }

    public function updateItemQty(int $itemId, int $qty): void
    {
        $item = $this->transaction->items()->find($itemId);
        if (! $item) {
            return;
        }

        if ($qty <= 0) {
            $item->delete();
        } else {
            $item->update(['quantity' => $qty]);
        }

        $this->transaction->recalculateTotalQty();
        $this->refreshTransaction();

        Notification::make()
            ->title('Qty diperbarui')
            ->success()
            ->duration(1500)
            ->send();
    }

    public function updateItemLot(int $itemId, string $lotNumber): void
    {
        $item = $this->transaction->items()->find($itemId);
        if (! $item) {
            return;
        }

        $item->update(['lot_number' => trim($lotNumber) ?: null]);
        $this->refreshTransaction();

        Notification::make()
            ->title('No. Lot diperbarui')
            ->success()
            ->duration(1500)
            ->send();
    }

    public function removeItem(int $itemId): void
    {
        OutboundTransactionItem::where('id', $itemId)
            ->where('outbound_transaction_id', $this->transaction->id)
            ->delete();

        $this->transaction->recalculateTotalQty();
        $this->refreshTransaction();

        Notification::make()
            ->title('Item dihapus')
            ->warning()
            ->duration(1500)
            ->send();
    }

    public function addItem(): void
    {
        if (! $this->addProductId) {
            Notification::make()
                ->title('Pilih produk terlebih dahulu')
                ->warning()
                ->send();

            return;
        }

        $product = Product::find($this->addProductId);
        if (! $product) {
            return;
        }

        $existingItem = $this->transaction->items()
            ->where('product_id', $product->id)
            ->first();

        if ($existingItem) {
            $existingItem->increment('quantity', max(1, $this->addQty));
        } else {
            $this->transaction->items()->create([
                'product_id' => $product->id,
                'quantity' => max(1, $this->addQty),
                'lot_number' => trim($this->addLotNumber) ?: null,
                'scanned_at' => now(),
            ]);
        }

        $this->transaction->recalculateTotalQty();
        $this->refreshTransaction();

        $this->addProductId = null;
        $this->addQty = 1;
        $this->addLotNumber = '';

        Notification::make()
            ->title('Item ditambahkan')
            ->body("{$product->code} — {$product->name}")
            ->success()
            ->duration(2000)
            ->send();
    }

    protected function refreshTransaction(): void
    {
        $this->transaction = OutboundTransaction::with(['items.product'])->findOrFail($this->transaction->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali ke Detail')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(OutboundTransactionResource::getUrl('view', ['record' => $this->transaction->id])),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'items' => $this->transaction->items()->with('product')->orderByDesc('scanned_at')->get(),
            'products' => Product::orderBy('code')->get(['id', 'code', 'name']),
        ];
    }
}
