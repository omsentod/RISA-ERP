<?php

namespace App\Filament\Resources\OutboundTransactionResource\Pages;

use App\Domain\Product\Models\Product;
use App\Domain\Shared\Enums\DateRangePreset;
use App\Domain\Stock\Actions\StartOutboundSession;
use App\Domain\Stock\Exports\RekapProdukKeluarExport;
use App\Domain\Stock\Models\OutboundTransaction;
use App\Filament\Concerns\HasSelectionToggle;
use App\Filament\Pages\ScanOutbound;
use App\Filament\Resources\OutboundTransactionResource;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Maatwebsite\Excel\Facades\Excel;

class ListOutboundTransactions extends ListRecords
{
    use HasSelectionToggle;

    protected static string $resource = OutboundTransactionResource::class;

    #[Url(as: 'mode', keep: true)]
    public string $viewMode = 'transaksi';

    #[Url(as: 'period', keep: true)]
    public string $rekapPreset = 'this_month';

    #[Url(as: 'from', keep: true)]
    public ?string $rekapFrom = null;

    #[Url(as: 'until', keep: true)]
    public ?string $rekapUntil = null;

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

    public function applyRekapFilter(array $data): void
    {
        $this->rekapPreset = $data['preset'] ?? DateRangePreset::ThisMonth->value;
        $this->rekapFrom = $data['from'] ?? null;
        $this->rekapUntil = $data['until'] ?? null;

        if (method_exists($this, 'resetTable')) {
            $this->resetTable();
        }
    }

    /**
     * Resolve preset ke [Carbon $from, Carbon $until].
     */
    protected function getRekapDateRange(): array
    {
        $preset = DateRangePreset::tryFrom($this->rekapPreset) ?? DateRangePreset::ThisMonth;

        return $preset->range($this->rekapFrom, $this->rekapUntil);
    }

    public function getRekapPeriodLabel(): string
    {
        $preset = DateRangePreset::tryFrom($this->rekapPreset) ?? DateRangePreset::ThisMonth;

        return $preset === DateRangePreset::Custom
            ? $preset->humanRange($this->rekapFrom, $this->rekapUntil)
            : $preset->label();
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
        [$from, $until] = $this->getRekapDateRange();

        // Sub-query closure: hanya items dari transaksi completed dalam range tanggal.
        $itemsInRange = fn (Builder $q) => $q
            ->whereBetween('scanned_at', [$from, $until])
            ->whereHas('transaction', fn (Builder $qq) => $qq->where('status', OutboundTransaction::STATUS_COMPLETED));

        return $table
            ->query(
                Product::query()
                    ->select('products.*')
                    ->whereHas('outboundItems', $itemsInRange)
                    ->withSum(['outboundItems as total_qty_out' => $itemsInRange], 'quantity')
                    ->withCount(['outboundItems as trx_count' => $itemsInRange])
                    ->withMax(['outboundItems as last_out_at' => $itemsInRange], 'scanned_at')
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
            ->recordUrl(fn (Product $record) => ProductResource::getUrl('view', ['record' => $record->id]))
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    /**
     * Header actions — di-evaluate lazy per render supaya viewMode berubah langsung memperbarui action set.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleViewMode')
                ->label(fn () => $this->viewMode === 'rekap' ? 'Lihat Surat Jalan' : 'Lihat Rekap Produk')
                ->icon(fn () => $this->viewMode === 'rekap' ? 'heroicon-o-document-text' : 'heroicon-o-chart-bar')
                ->color('gray')
                ->action('toggleViewMode'),

            Actions\Action::make('filterPeriode')
                ->label(fn () => 'Periode: ' . $this->getRekapPeriodLabel())
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->visible(fn () => $this->viewMode === 'rekap')
                ->fillForm(fn () => [
                    'preset' => $this->rekapPreset,
                    'from' => $this->rekapFrom,
                    'until' => $this->rekapUntil,
                ])
                ->form([
                    Forms\Components\Select::make('preset')
                        ->label('Periode')
                        ->options(DateRangePreset::options())
                        ->default(DateRangePreset::ThisMonth->value)
                        ->live()
                        ->required(),
                    Forms\Components\DatePicker::make('from')
                        ->label('Dari tanggal')
                        ->native(false)
                        ->visible(fn (Get $get) => $get('preset') === DateRangePreset::Custom->value)
                        ->required(fn (Get $get) => $get('preset') === DateRangePreset::Custom->value),
                    Forms\Components\DatePicker::make('until')
                        ->label('Sampai tanggal')
                        ->native(false)
                        ->visible(fn (Get $get) => $get('preset') === DateRangePreset::Custom->value)
                        ->required(fn (Get $get) => $get('preset') === DateRangePreset::Custom->value)
                        ->afterOrEqual('from'),
                ])
                ->action(fn (array $data) => $this->applyRekapFilter($data))
                ->modalWidth('md')
                ->modalHeading('Filter Periode Laporan')
                ->modalSubmitActionLabel('Terapkan'),

            Actions\Action::make('exportRekap')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => $this->viewMode === 'rekap')
                ->action(function () {
                    [$from, $until] = $this->getRekapDateRange();
                    $filename = 'rekap-produk-keluar_' . $from->format('Ymd') . '-' . $until->format('Ymd') . '_' . now()->format('His') . '.xlsx';

                    return Excel::download(
                        new RekapProdukKeluarExport($from, $until, $this->getRekapPeriodLabel()),
                        $filename
                    );
                }),

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
