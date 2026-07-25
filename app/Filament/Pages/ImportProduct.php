<?php

namespace App\Filament\Pages;

use App\Domain\Import\Actions\ApplyProductImport;
use App\Domain\Import\Actions\GenerateProductTemplate;
use App\Domain\Import\Data\ProductImportRow;
use App\Domain\Import\Parsers\ProductImportParser;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportProduct extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Import Produk';

    protected static ?string $title = 'Import Produk dari Excel';

    protected static ?int $navigationSort = 40;

    protected static string $view = 'filament.pages.import-product';

    public ?array $data = [];

    public string $step = 'upload';

    public ?string $uploadedPath = null;

    public array $rows = [];

    public string $duplicateStrategy = 'skip';

    public ?array $result = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('file')
                    ->label('File Excel (.xlsx)')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->disk('local')
                    ->directory('imports')
                    ->required()
                    ->maxSize(10240)
                    ->helperText('Format sesuai template: 2 sheet (NON LOCKING, LOCKING) dengan kolom Spesifikasi | Kode | Nama Produk | NIE'),
            ])
            ->statePath('data');
    }

    public function downloadTemplate(): StreamedResponse
    {
        return app(GenerateProductTemplate::class)->stream();
    }

    public function parseUploadedFile(): void
    {
        $data = $this->form->getState();
        $path = $data['file'] ?? null;

        if (!$path) {
            Notification::make()->title('File belum dipilih')->danger()->send();

            return;
        }

        $absolutePath = Storage::disk('local')->path($path);

        if (!file_exists($absolutePath)) {
            Notification::make()->title('File tidak ditemukan di storage')->danger()->send();

            return;
        }

        try {
            $parsed = app(ProductImportParser::class)->parse($absolutePath);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal parse file Excel')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->rows = array_map(fn (ProductImportRow $row) => $row->toArray(), $parsed);
        $this->uploadedPath = $path;
        $this->step = 'preview';

        Notification::make()
            ->title('Berhasil parse ' . count($this->rows) . ' baris')
            ->success()
            ->send();
    }

    public function applyImport(): void
    {
        $rows = array_map(fn (array $r) => ProductImportRow::fromArray($r), $this->rows);
        $summary = app(ApplyProductImport::class)->handle($rows, $this->duplicateStrategy);

        $this->result = $summary;
        $this->step = 'done';

        if ($this->uploadedPath) {
            Storage::disk('local')->delete($this->uploadedPath);
            $this->uploadedPath = null;
        }

        Notification::make()
            ->title('Import selesai')
            ->body(sprintf(
                '%d ditambah, %d di-update, %d di-skip, %d invalid',
                $summary['inserted'],
                $summary['updated'],
                $summary['skipped'],
                $summary['invalid']
            ))
            ->success()
            ->send();
    }

    public function resetImport(): void
    {
        if ($this->uploadedPath) {
            Storage::disk('local')->delete($this->uploadedPath);
        }
        $this->uploadedPath = null;
        $this->rows = [];
        $this->result = null;
        $this->duplicateStrategy = 'skip';
        $this->step = 'upload';
        $this->form->fill();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->downloadTemplate()),
        ];
    }

    public function getStats(): array
    {
        $new = 0;
        $duplicate = 0;
        $invalid = 0;
        foreach ($this->rows as $row) {
            match ($row['status']) {
                ProductImportRow::STATUS_NEW => $new++,
                ProductImportRow::STATUS_DUPLICATE => $duplicate++,
                ProductImportRow::STATUS_INVALID => $invalid++,
                default => null,
            };
        }

        return compact('new', 'duplicate', 'invalid');
    }

    public function getRowsByStatus(string $status): array
    {
        return array_values(array_filter($this->rows, fn ($r) => $r['status'] === $status));
    }
}
