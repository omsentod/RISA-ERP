<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Domain\Import\Actions\ApplyProductImport;
use App\Domain\Import\Actions\GenerateProductTemplate;
use App\Domain\Import\Data\ProductImportRow;
use App\Domain\Import\Parsers\ProductImportParser;
use App\Domain\Product\Actions\BuildPrintBarcodeJs;
use App\Filament\Concerns\HasSelectionToggle;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListProducts extends ListRecords
{
    use HasSelectionToggle;

    protected static string $resource = ProductResource::class;

    /** @var array<string, mixed> Memo hasil parse preview per-request */
    private array $importPreviewCache = [];

    protected function getHeaderActions(): array
    {
        return [
            $this->getSelectionToggleAction(),
            Actions\CreateAction::make()->label('New Produk'),
            Actions\ActionGroup::make([
                Actions\Action::make('import')
                    ->label('Import Excel')
                    ->icon('heroicon-o-document-arrow-up')
                    ->modalHeading('Import Produk dari Excel')
                    ->modalDescription('Upload file Excel (.xlsx) sesuai format template.')
                    ->modalWidth('lg')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('File Excel (.xlsx)')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->disk('local')
                            ->directory('imports')
                            ->required()
                            ->maxSize(10240)
                            ->live()
                            ->helperText('File Excel (.xlsx) dengan kolom Spesifikasi | Kode | Nama Produk | NIE | QTY | Kode Golongan per sheet kategori.'),
                        Forms\Components\Placeholder::make('preview')
                            ->label('Preview (cek dulu sebelum import)')
                            ->visible(fn (Forms\Get $get) => filled($get('file')))
                            ->content(fn (Forms\Get $get) => view('filament.components.import-preview', [
                                'result' => $this->previewImportRows($get('file')),
                            ])),
                        Forms\Components\Radio::make('duplicate_strategy')
                            ->label('Penanganan Data Duplikat')
                            ->options([
                                'skip' => 'Skip yang sama persis (baris identik dengan produk existing tidak ditambah)',
                                'create_new' => 'Buat produk baru (tetap tambahkan semua baris valid)',
                            ])
                            ->default('skip')
                            ->required(),
                    ])
                    ->modalSubmitActionLabel('Mulai Import')
                    ->action(function (array $data) {
                        $path = $data['file'] ?? null;
                        if (!$path) {
                            Notification::make()->title('File belum dipilih')->danger()->send();

                            return;
                        }

                        $absolutePath = Storage::disk('local')->path($path);

                        if (!file_exists($absolutePath)) {
                            Notification::make()->title('File tidak ditemukan')->danger()->send();

                            return;
                        }

                        try {
                            $parsed = app(ProductImportParser::class)->parse($absolutePath);
                            $summary = app(ApplyProductImport::class)->handle($parsed, $data['duplicate_strategy']);

                            Storage::disk('local')->delete($path);

                            Notification::make()
                                ->title('Import Excel Selesai')
                                ->body(sprintf(
                                    '%d ditambah, %d di-skip, %d invalid',
                                    $summary['inserted'],
                                    $summary['skipped'],
                                    $summary['invalid']
                                ))
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal memproses file Excel')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->extraModalFooterActions([
                        Actions\Action::make('downloadTemplate')
                            ->label('Download Template Excel')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('gray')
                            ->action(fn () => app(GenerateProductTemplate::class)->stream()),
                    ]),
                Actions\Action::make('printAllFiltered')
                    ->label('Cetak Semua')
                    ->icon('heroicon-o-printer')
                    ->modalHeading('Cetak Label untuk Semua Produk yang Sedang Difilter')
                    ->modalDescription('Akan trigger pop-up cetak untuk semua produk yang sesuai filter/pencarian saat ini. Maksimum 200 produk per batch.')
                    ->form(ProductResource::lotPeriodFields())
                    ->action(function (array $data) {
                        $ids = $this->getFilteredTableQuery()->limit(200)->pluck('id')->all();
                        if (empty($ids)) {
                            Notification::make()->title('Tidak ada produk untuk dicetak')->warning()->send();

                            return;
                        }

                        $this->js(app(BuildPrintBarcodeJs::class)->handle($ids, null, null, null, ProductResource::resolveLotPeriod($data)));
                    }),
            ])
                ->label('Lainnya')
                ->icon('heroicon-o-ellipsis-vertical')
                ->color('gray')
                ->button(),
        ];
    }

    /**
     * Parse file yang diupload untuk preview import (memoized per-request).
     *
     * @return array<int, ProductImportRow>|array{__error: string}|null
     */
    private function previewImportRows(mixed $state): ?array
    {
        $value = is_array($state) ? (reset($state) ?: null) : $state;

        if ($value instanceof TemporaryUploadedFile) {
            $absolutePath = $value->getRealPath();
            $key = 'tmp:' . $absolutePath;
        } elseif (is_string($value) && $value !== '') {
            $absolutePath = Storage::disk('local')->path($value);
            $key = $value;
        } else {
            return null;
        }

        if (array_key_exists($key, $this->importPreviewCache)) {
            return $this->importPreviewCache[$key];
        }

        if (!is_file($absolutePath)) {
            return $this->importPreviewCache[$key] = null;
        }

        try {
            return $this->importPreviewCache[$key] = app(ProductImportParser::class)->parse($absolutePath);
        } catch (\Throwable $e) {
            return $this->importPreviewCache[$key] = ['__error' => $e->getMessage()];
        }
    }
}
