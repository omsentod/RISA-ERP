<?php

namespace App\Filament\Resources;

use App\Domain\Product\Models\Product;
use App\Filament\Resources\ProductResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Produk';

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
                        Forms\Components\Select::make('product_category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->unique('product_categories', 'name'),
                                Forms\Components\Toggle::make('is_locking')
                                    ->label('Kategori Locking'),
                            ]),
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
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record?->category?->is_locking ? 'info' : 'gray')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record?->category?->name),
                Tables\Columns\TextColumn::make('registration.nie_number')
                    ->label('NIE')
                    ->searchable()
                    ->badge()
                    ->color('success'),
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
                Tables\Filters\Filter::make('locking_category')
                    ->label('Kategori Locking')
                    ->query(fn (Builder $query) => $query->whereHas('category', fn ($q) => $q->where('is_locking', true))),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
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
        return ['code', 'name'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Kategori' => $record->category?->name,
            'NIE' => $record->registration?->nie_number,
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
