<?php

namespace App\Filament\Resources;

use App\Domain\Registration\Models\Registration;
use App\Filament\Resources\RegistrationResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RegistrationResource extends Resource
{
    protected static ?string $model = Registration::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'NIE (Nomor Izin Edar)';

    protected static ?string $modelLabel = 'NIE';

    protected static ?string $pluralModelLabel = 'NIE';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi NIE')
                    ->schema([
                        Forms\Components\TextInput::make('nie_number')
                            ->label('Nomor NIE')
                            ->placeholder('Contoh: AKD 21302420095')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Forms\Components\TextInput::make('issuer')
                            ->label('Diterbitkan oleh')
                            ->default('BPOM')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\DatePicker::make('issued_at')
                            ->label('Tanggal Terbit')
                            ->native(false),
                        Forms\Components\DatePicker::make('expired_at')
                            ->label('Tanggal Expired')
                            ->native(false)
                            ->afterOrEqual('issued_at'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Detail Tambahan')
                    ->schema([
                        Forms\Components\FileUpload::make('attachment_path')
                            ->label('Lampiran (scan NIE)')
                            ->disk('local')
                            ->directory('nie-attachments')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120),
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
                Tables\Columns\TextColumn::make('nie_number')
                    ->label('Nomor NIE')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('issuer')
                    ->label('Penerbit')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('issued_at')
                    ->label('Terbit')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('Expired')
                    ->date('d M Y')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record?->isExpired() => 'danger',
                        $record?->isExpiringSoon() => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) {
                            return '—';
                        }
                        $label = $state->format('d M Y');
                        if ($record?->isExpired()) {
                            return $label . ' (Expired)';
                        }
                        if ($record?->isExpiringSoon()) {
                            return $label . ' (Segera Expired)';
                        }

                        return $label;
                    }),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Jumlah Produk')
                    ->counts('products')
                    ->badge(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('expired')
                    ->label('Sudah Expired')
                    ->query(fn (Builder $query) => $query->whereDate('expired_at', '<', now())),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Segera Expired (3 bulan)')
                    ->query(fn (Builder $query) => $query
                        ->whereDate('expired_at', '>=', now())
                        ->whereDate('expired_at', '<=', now()->addMonths(3))
                    ),
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
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nie_number'];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Registration::whereDate('expired_at', '<=', now()->addMonths(3))->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegistrations::route('/'),
            'create' => Pages\CreateRegistration::route('/create'),
            'view' => Pages\ViewRegistration::route('/{record}'),
            'edit' => Pages\EditRegistration::route('/{record}/edit'),
        ];
    }
}
