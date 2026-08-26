<?php

namespace App\Filament\Pages;

use App\Domain\Stock\Actions\AddScanToOutbound;
use App\Domain\Stock\Models\OutboundTransaction;
use App\Domain\Stock\Models\OutboundTransactionItem;
use App\Filament\Resources\OutboundTransactionResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ScanOutbound extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationGroup = 'Produk';

    protected static ?string $slug = 'produk-keluar/{transaction}/scan';

    protected static string $view = 'filament.pages.scan-outbound';

    protected static ?string $title = 'Sesi Scan Barang Keluar';

    public OutboundTransaction $transaction;

    public string $scanInput = '';

    public string $destination = '';

    public string $notes = '';

    public array $pickerCandidates = [];

    public int $pickerQty = 1;

    public ?string $pickerCode = null;

    public function mount(OutboundTransaction $transaction): void
    {
        $this->transaction = $transaction->load(['items.product']);

        if (!$this->transaction->isDraft()) {
            Notification::make()
                ->title('Transaksi sudah ' . ($this->transaction->isCompleted() ? 'diselesaikan' : 'dibatalkan'))
                ->body('Redirect ke halaman daftar.')
                ->warning()
                ->send();
            redirect(OutboundTransactionResource::getUrl('view', ['record' => $this->transaction->id]));

            return;
        }

        $this->destination = (string) $this->transaction->destination;
        $this->notes = (string) $this->transaction->notes;
    }

    public function submitScan(): void
    {
        $code = trim($this->scanInput);
        $this->scanInput = '';

        if ($code === '') {
            return;
        }

        try {
            $result = app(AddScanToOutbound::class)->handle($this->transaction, $code);
        } catch (AmbiguousScanException $e) {
            $this->openPicker($e);

            return;
        } catch (\Throwable $e) {
            Notification::make()->title('Scan gagal')->body($e->getMessage())->danger()->send();

            return;
        }

        $this->notifyScanResult($result);
    }

    public function pickProduct(int $productId): void
    {
        if (!in_array($productId, array_column($this->pickerCandidates, 'id'), true)) {
            return;
        }

        $product = Product::find($productId);
        if (!$product) {
            return;
        }

        try {
            $result = app(AddScanToOutbound::class)->addProduct($this->transaction, $product, $this->pickerQty);
        } catch (\Throwable $e) {
            Notification::make()->title('Gagal menambah item')->body($e->getMessage())->danger()->send();

            return;
        }

        $this->closePicker();
        $this->notifyScanResult($result);
    }

    public function closePicker(): void
    {
        $this->pickerCandidates = [];
        $this->pickerCode = null;
        $this->pickerQty = 1;
        $this->dispatch('close-modal', id: 'pick-product');
    }

    private function openPicker(AmbiguousScanException $e): void
    {
        $this->pickerCode = $e->scannedCode;
        $this->pickerQty = $e->qtyToAdd;
        $this->pickerCandidates = $e->candidates
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'specification' => $p->specification,
            ])
            ->all();
        $this->dispatch('open-modal', id: 'pick-product');
    }

    private function notifyScanResult(array $result): void
    {
        $this->refreshTransaction();

        ['item' => $item, 'isNew' => $isNew, 'qtyAdded' => $qtyAdded] = $result;

        Notification::make()
            ->title($isNew ? 'Item ditambahkan' : 'Qty di-update')
            ->body("{$item->product->code} — qty sekarang {$item->quantity} (+{$qtyAdded} pcs)")
            ->success()
            ->duration(2000)
            ->send();
    }

    public function incrementItemQty(int $itemId): void
    {
        $item = $this->transaction->items()->find($itemId);
        if (!$item) {
            return;
        }
        $item->increment('quantity');
        $this->transaction->recalculateTotalQty();
        $this->refreshTransaction();
    }

    public function decrementItemQty(int $itemId): void
    {
        $item = $this->transaction->items()->find($itemId);
        if (!$item) {
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

    public function removeItem(int $itemId): void
    {
        OutboundTransactionItem::where('id', $itemId)
            ->where('outbound_transaction_id', $this->transaction->id)
            ->delete();
        $this->transaction->recalculateTotalQty();
        $this->refreshTransaction();
    }

    public function updateHeaderInfo(): void
    {
        $this->transaction->update([
            'destination' => $this->destination ?: null,
            'notes' => $this->notes ?: null,
            'updated_by' => auth()->id(),
        ]);
        Notification::make()->title('Info dokumen disimpan')->success()->duration(1500)->send();
    }

    public function completeSession()
    {
        if ($this->transaction->items()->count() === 0) {
            Notification::make()->title('Belum ada item')->body('Scan minimal 1 produk sebelum menyelesaikan sesi.')->warning()->send();

            return null;
        }

        $this->transaction->update([
            'status' => OutboundTransaction::STATUS_COMPLETED,
            'completed_at' => now(),
            'destination' => $this->destination ?: null,
            'notes' => $this->notes ?: null,
            'updated_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Sesi selesai')
            ->body("Surat jalan {$this->transaction->doc_no} tersimpan dengan {$this->transaction->total_qty} unit.")
            ->success()
            ->send();

        return redirect(OutboundTransactionResource::getUrl('view', ['record' => $this->transaction->id]));
    }

    public function cancelSession()
    {
        $this->transaction->update([
            'status' => OutboundTransaction::STATUS_CANCELLED,
            'completed_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        Notification::make()->title('Sesi dibatalkan')->warning()->send();

        return redirect(OutboundTransactionResource::getUrl('index'));
    }

    protected function refreshTransaction(): void
    {
        $this->transaction = OutboundTransaction::with(['items.product'])->find($this->transaction->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(OutboundTransactionResource::getUrl('index')),
        ];
    }
}
