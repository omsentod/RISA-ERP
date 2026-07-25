<?php

namespace App\Filament\Resources;

use App\Domain\Stock\Actions\BuildPrintSuratJalanJs;
use App\Domain\Stock\Models\OutboundTransaction;
use App\Filament\Pages\ScanOutbound;
use App\Filament\Resources\OutboundTransactionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OutboundTransactionResource extends Resource
{
    protected static ?string $model = OutboundTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Produk Keluar';

    protected static ?string $modelLabel = 'Produk Keluar';

    protected static ?string $pluralModelLabel = 'Produk Keluar';

    protected static ?int $navigationSort = 30;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Info Dokumen')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('doc_no')->label('No. Surat Jalan')->weight('bold')->copyable(),
                    Infolists\Components\TextEntry::make('doc_date')->label('Tanggal')->date('d M Y'),
                    Infolists\Components\TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'draft' => 'Draft', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', default => $state,
                        })
                        ->color(fn ($state) => match ($state) {
                            'draft' => 'warning', 'completed' => 'success', 'cancelled' => 'danger', default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('destination')->label('Tujuan')->placeholder('—'),
                    Infolists\Components\TextEntry::make('creator.name')->label('Dibuat oleh')->placeholder('—'),
                    Infolists\Components\TextEntry::make('total_qty')->label('Total Unit')->badge()->color('info'),
                    Infolists\Components\TextEntry::make('started_at')->label('Mulai')->dateTime('d M Y H:i')->placeholder('—'),
                    Infolists\Components\TextEntry::make('completed_at')->label('Selesai')->dateTime('d M Y H:i')->placeholder('—'),
                    Infolists\Components\TextEntry::make('notes')->label('Catatan')->placeholder('—')->columnSpanFull(),
                ]),
            Infolists\Components\Section::make('Daftar Item')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            Infolists\Components\TextEntry::make('product.code')->label('Kode')->weight('medium'),
                            Infolists\Components\TextEntry::make('product.name')->label('Nama Produk')->columnSpan(2),
                            Infolists\Components\TextEntry::make('quantity')->label('Qty')->badge()->color('gray'),
                        ]),
                ]),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Info Dokumen')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('doc_no')
                            ->label('No. Surat Jalan')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DatePicker::make('doc_date')
                            ->label('Tanggal')
                            ->native(false)
                            ->required(),
                        Forms\Components\TextInput::make('destination')
                            ->label('Tujuan')
                            ->placeholder('Nama RS / customer')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('doc_no')
                    ->label('No. Surat Jalan')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('doc_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('destination')
                    ->label('Tujuan')
                    ->searchable()
                    ->placeholder('—')
                    ->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'draft' => 'Draft (belum selesai)',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'draft' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Total Qty')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jenis Item')
                    ->counts('items')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat oleh')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft (belum selesai)',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ]),
                Tables\Filters\Filter::make('doc_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari tanggal')->native(false),
                        Forms\Components\DatePicker::make('until')->label('Sampai tanggal')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('doc_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('doc_date', '<=', $date));
                    }),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('continueScan')
                    ->label('Lanjut Scan')
                    ->icon('heroicon-o-qr-code')
                    ->color('warning')
                    ->visible(fn (OutboundTransaction $record) => $record->isDraft())
                    ->url(fn (OutboundTransaction $record) => ScanOutbound::getUrl(['transaction' => $record->id])),
                Tables\Actions\Action::make('printSuratJalan')
                    ->label('Cetak SJ')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->visible(fn (OutboundTransaction $record) => $record->isCompleted())
                    ->action(function (OutboundTransaction $record, $livewire) {
                        $livewire->js(app(BuildPrintSuratJalanJs::class)->handle($record));
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (OutboundTransaction $record) => !$record->isCancelled()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->requiresConfirmation(),
                    Tables\Actions\ForceDeleteBulkAction::make()->requiresConfirmation(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['creator']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['doc_no', 'destination'];
    }

    public static function getNavigationBadge(): ?string
    {
        $drafts = OutboundTransaction::where('status', OutboundTransaction::STATUS_DRAFT)->count();

        return $drafts > 0 ? (string) $drafts : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Jumlah sesi scan yang masih draft (belum selesai)';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOutboundTransactions::route('/'),
            'view' => Pages\ViewOutboundTransaction::route('/{record}'),
            'edit' => Pages\EditOutboundTransaction::route('/{record}/edit'),
        ];
    }
}
