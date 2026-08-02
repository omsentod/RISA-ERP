<?php

namespace App\Filament\Resources;

use App\Domain\Product\Actions\BuildPrintBarcodeJs;
use App\Domain\Product\Models\Product;
use App\Filament\Resources\ProductResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Produk';

    protected static ?string $navigationLabel = 'Daftar Produk';

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode SKU')
                            ->placeholder('Contoh: OF 1010 04')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Produk')
                            ->placeholder('Contoh: 4.5 mm Semi Tubular Plate 4 Holes')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('specification')
                            ->label('Spesifikasi')
                            ->placeholder('Contoh: Bone plate large fragment plate non locking stainless steel non steril')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('product_category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('registration_id')
                            ->label('NIE')
                            ->relationship('registration', 'nie_number')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nie_number')
                                    ->label('Nomor NIE')
                                    ->required()
                                    ->unique('registrations', 'nie_number'),
                                Forms\Components\DatePicker::make('issued_at')->label('Terbit'),
                                Forms\Components\DatePicker::make('expired_at')->label('Expired'),
                            ]),
                        Forms\Components\TextInput::make('product_group_code')
                            ->label('Kode Golongan Produk')
                            ->placeholder('Contoh: 01, 03')
                            ->maxLength(20)
                            ->helperText('Kode golongan dari Excel (misal: 01, 03)'),
                        Forms\Components\TextInput::make('default_quantity')
                            ->label('Quantity Default (QTY)')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->helperText('Jumlah quantity default untuk cetak label.'),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Publikasi ke Website')
                    ->description('Kelola apakah produk ini tampil di company profile website.')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('Terbit di website')
                            ->helperText('Fitur publish/takedown ke company profile akan aktif di Fase 2.'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->sortable()
                    ->wrap()
                    ->limit(60),
                Tables\Columns\TextColumn::make('product_group_code')
                    ->label('Kode Golongan')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('specification')
                    ->label('Spesifikasi')
                    ->searchable()
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn ($record) => $record?->specification)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('registration.nie_number')
                    ->label('NIE')
                    ->searchable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('default_quantity')
                    ->label('QTY')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Website')
                    ->boolean()
                    ->toggleable(),
            ])
            ->defaultSort('code')
            ->filters([
                Tables\Filters\SelectFilter::make('product_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('registration_id')
                    ->label('NIE')
                    ->relationship('registration', 'nie_number')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Status Publikasi')
                    ->trueLabel('Sudah dipublish')
                    ->falseLabel('Belum dipublish'),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('printLabel')
                    ->label('Cetak Label')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->form([
                        Forms\Components\TextInput::make('sequence')
                            ->label('Kode Urutan Hari (3 Digit)')
                            ->default(fn () => app(\App\Domain\Product\Actions\GenerateDynamicLot::class)->getTodaySequenceString())
                            ->maxLength(3)
                            ->numeric()
                            ->required()
                            ->helperText(function (Product $record) {
                                $fullLot = app(\App\Domain\Product\Actions\GenerateDynamicLot::class)->handle($record);
                                return "Nomor LOT otomatis yang akan tercetak: {$fullLot} (Golongan + YY + MM + Urutan).";
                            }),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Jumlah Duplikat Lembar Label')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->helperText('Berapa kali stiker label ini akan diprint (jumlah lembar).'),
                    ])
                    ->action(function (Product $record, array $data, $livewire) {
                        $livewire->js(app(BuildPrintBarcodeJs::class)->handle([$record->id], $data['sequence'], (int) $data['quantity']));
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('printLabelsBulk')
                    ->label('Cetak Label Terpilih')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->mountUsing(function (Forms\ComponentContainer $form, Collection $records) {
                        $items = [];
                        foreach ($records as $record) {
                            $items[] = [
                                'product_id' => $record->id,
                                'code' => $record->code,
                                'name' => $record->name,
                                'quantity' => 1,
                            ];
                        }
                        $form->fill([
                            'sequence' => app(\App\Domain\Product\Actions\GenerateDynamicLot::class)->getTodaySequenceString(),
                            'items' => $items,
                        ]);
                    })
                    ->form([
                        Forms\Components\TextInput::make('sequence')
                            ->label('Kode Urutan Hari (3 Digit)')
                            ->default(fn () => app(\App\Domain\Product\Actions\GenerateDynamicLot::class)->getTodaySequenceString())
                            ->maxLength(3)
                            ->numeric()
                            ->required()
                            ->helperText('Kode 3 digit urutan harian ini akan berlaku untuk seluruh produk terpilih di bawah.'),
                        Forms\Components\Repeater::make('items')
                            ->label('Daftar Produk Terpilih & Jumlah Duplikat Per Item')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->schema([
                                Forms\Components\Hidden::make('product_id'),
                                Forms\Components\TextInput::make('code')
                                    ->label('Kode SKU')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Produk')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Jumlah Duplikat Lembar')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),
                            ])
                            ->columns(3),
                    ])
                    ->action(function (array $data, $livewire) {
                        $sequence = $data['sequence'] ?? null;
                        $items = $data['items'] ?? [];
                        $livewire->js(app(BuildPrintBarcodeJs::class)->handle($items, $sequence));
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->requiresConfirmation(),
                    Tables\Actions\ForceDeleteBulkAction::make()->requiresConfirmation(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->deferLoading();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['category', 'registration']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'name', 'specification'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Kategori' => $record->category?->name,
            'NIE' => $record->registration?->nie_number,
            'Spesifikasi' => Str::limit($record->specification ?? '', 100),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Product::count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
