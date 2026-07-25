<?php

namespace App\Filament\Resources\OutboundTransactionResource\Pages;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Actions\StartOutboundSession;
use App\Domain\Stock\Models\OutboundTransaction;
use App\Filament\Concerns\HasSelectionToggle;
use App\Filament\Pages\ScanOutbound;
use App\Filament\Resources\OutboundTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListOutboundTransactions extends ListRecords
{
    use HasSelectionToggle;

    protected static string $resource = OutboundTransactionResource::class;

    #[Url(as: 'mode', keep: true)]
    public string $viewMode = 'transaksi';

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'transaksi' ? 'rekap' : 'transaksi';
        $this->tableSortColumn = null;
        $this->tableSortDirection = null;
        $this->tableSearch = null;
        $this->tableFilters = [];
        if (method_exists($this, 'resetTable')) {
            $this->resetTable();
        }
    }

    /**
     * Override table() — gabungkan logika viewMode switch + HasSelectionToggle
     * agar kedua fitur tidak saling timpa.
     */
    public function table(Table $table): Table
    {
        if ($this->viewMode === 'rekap') {
            return $this->buildRekapTable($table);
        }

        // Mode transaksi: pakai table() dari parent (OutboundTransactionResource),
        // lalu terapkan logika selectMode dari trait.
        $table = parent::table($table);

        if (!$this->selectMode) {
            $table->bulkActions([]);
        }

        return $table;
    }

    protected function buildRekapTable(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->select('products.*')
                    ->whereHas('outboundItems.transaction', fn (Builder $q) => $q->where('status', OutboundTransaction::STATUS_COMPLETED))
                    ->withSum(
                        ['outboundItems as total_qty_out' => fn (Builder $q) => $q->whereHas('transaction', fn (Builder $qq) => $qq->where('status', OutboundTransaction::STATUS_COMPLETED))],
                        'quantity'
                    )
                    ->withCount(
                        ['outboundItems as trx_count' => fn (Builder $q) => $q->whereHas('transaction', fn (Builder $qq) => $qq->where('status', OutboundTransaction::STATUS_COMPLETED))]
                    )
                    ->withMax(
                        ['outboundItems as last_out_at' => fn (Builder $q) => $q->whereHas('transaction', fn (Builder $qq) => $qq->where('status', OutboundTransaction::STATUS_COMPLETED))],
                        'scanned_at'
                    )
            )
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->wrap()
                    ->limit(60),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_qty_out')
                    ->label('Total Qty Keluar')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('trx_count')
                    ->label('Jumlah Transaksi')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('last_out_at')
                    ->label('Terakhir Keluar')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->defaultSort('total_qty_out', 'desc')
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    /**
     * Header actions — gunakan ->visible(fn() => ...) agar setiap action
     * di-evaluate secara lazy per render cycle, bukan di-cache saat mount pertama.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleViewMode')
                ->label(fn () => $this->viewMode === 'rekap' ? 'Lihat Surat Jalan' : 'Lihat Rekap Produk')
                ->icon(fn () => $this->viewMode === 'rekap' ? 'heroicon-o-document-text' : 'heroicon-o-chart-bar')
                ->color('gray')
                ->action('toggleViewMode'),
            $this->getSelectionToggleAction()
                ->visible(fn () => $this->viewMode === 'transaksi'),
            Actions\Action::make('startSession')
                ->label('Scan Produk')
                ->icon('heroicon-o-qr-code')
                ->color('primary')
                ->visible(fn () => $this->viewMode === 'transaksi')
                ->action(function () {
                    $transaction = app(StartOutboundSession::class)->handle();

                    return redirect(ScanOutbound::getUrl(['transaction' => $transaction->id]));
                }),
        ];
    }
}
