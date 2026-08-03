<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Tables\Table;
use Livewire\Attributes\Url;

/**
 * Reusable trait untuk List page yang butuh mode "multi-pilih" (bulk selection).
 *
 * Default: OFF → checkbox column & bulk actions tersembunyi total.
 * User klik toggle → ON → checkbox muncul → bisa select rows → bulk action bar Filament muncul.
 *
 * Cara pakai:
 *   1. `use HasSelectionToggle;` di ListRecords class
 *   2. Tambahkan `$this->getSelectionToggleAction()` di array getHeaderActions()
 *   3. Bulk actions tetap didefinisikan seperti biasa di Resource::table() — trait otomatis strip kalau selectMode off.
 */
trait HasSelectionToggle
{
    #[Url(as: 'select', keep: true)]
    public bool $selectMode = false;

    public function toggleSelectMode(): void
    {
        $this->selectMode = !$this->selectMode;
        // Filament caches table config at initial mount — reset so the
        // conditional bulkActions() strip in table() re-runs with new state.
        if (method_exists($this, 'resetTable')) {
            $this->resetTable();
        }
        $this->deselectAllTableRecords();
    }

    /**
     * Override untuk conditional-strip bulk actions.
     * Ketika selectMode OFF, Filament tidak render checkbox column atau bulk action bar sama sekali.
     */
    public function table(Table $table): Table
    {
        $table = parent::table($table);

        if (!$this->selectMode) {
            $table->bulkActions([]);
        } else {
            // Ketika multi-pilih ON, jangan reset selection saat user ganti
            // keyword search / filter — memungkinkan build up selection dari
            // beberapa kata kunci berbeda sebelum trigger bulk action.
            $table->deselectAllRecordsWhenFiltered(false);
        }

        return $table;
    }

    /**
     * Header action untuk toggle. Tambahkan ke getHeaderActions() di posisi yang diinginkan.
     */
    protected function getSelectionToggleAction(): Action
    {
        return Action::make('toggleSelectMode')
            ->label(fn () => $this->selectMode ? 'Nonaktifkan Multi-Pilih' : 'Aktifkan Multi-Pilih')
            ->icon(fn () => $this->selectMode ? 'heroicon-o-x-mark' : 'heroicon-o-check-circle')
            ->color(fn () => $this->selectMode ? 'danger' : 'gray')
            ->action('toggleSelectMode');
    }
}
