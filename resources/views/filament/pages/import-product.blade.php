<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

    {{-- STEP INDICATOR --}}
    <div class="flex items-center gap-4 mb-2">
        @foreach (['upload' => '1. Upload', 'preview' => '2. Preview', 'done' => '3. Selesai'] as $key => $label)
            @php
                $active = $step === $key;
                $done = ($step === 'preview' && $key === 'upload') || ($step === 'done' && in_array($key, ['upload', 'preview']));
            @endphp
            <div @class([
                'flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium',
                'bg-primary-500 text-white' => $active,
                'bg-success-500/20 text-success-700 dark:text-success-400' => $done,
                'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => !$active && !$done,
            ])>
                {{ $label }}
            </div>
        @endforeach
    </div>

    {{-- STEP 1: UPLOAD --}}
    @if ($step === 'upload')
        <x-filament::section>
            <x-slot name="heading">Upload File Excel</x-slot>
            <x-slot name="description">
                Upload file .xlsx dengan format sesuai template. Gunakan tombol "Download Template" di kanan atas untuk contoh.
            </x-slot>

            <form wire:submit="parseUploadedFile">
                {{ $this->form }}

                <div class="mt-4 flex gap-2">
                    <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                        Parse &amp; Preview
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    @endif

    {{-- STEP 2: PREVIEW --}}
    @if ($step === 'preview')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::section>
                <div class="text-sm text-gray-500 dark:text-gray-400">Baris Baru</div>
                <div class="text-3xl font-bold text-success-600 dark:text-success-400">{{ $stats['new'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Akan di-insert</div>
            </x-filament::section>
            <x-filament::section>
                <div class="text-sm text-gray-500 dark:text-gray-400">Duplikat (Kode sudah ada)</div>
                <div class="text-3xl font-bold text-warning-600 dark:text-warning-400">{{ $stats['duplicate'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Perlu keputusan: overwrite / skip</div>
            </x-filament::section>
            <x-filament::section>
                <div class="text-sm text-gray-500 dark:text-gray-400">Baris Invalid</div>
                <div class="text-3xl font-bold text-danger-600 dark:text-danger-400">{{ $stats['invalid'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Akan di-skip (Kode/Nama kosong)</div>
            </x-filament::section>
        </div>

        {{-- Duplicate resolution --}}
        @if ($stats['duplicate'] > 0)
            <x-filament::section>
                <x-slot name="heading">Keputusan untuk Baris Duplikat</x-slot>
                <x-slot name="description">
                    Ada {{ $stats['duplicate'] }} baris di file yang Kode-nya sudah ada di sistem. Pilih perilaku:
                </x-slot>

                <div class="flex flex-col sm:flex-row gap-3">
                    <label class="flex items-start gap-2 p-3 border rounded-md cursor-pointer flex-1 dark:border-gray-700"
                           :class="'{{ $duplicateStrategy }}' === 'skip' ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10' : ''">
                        <input type="radio" wire:model.live="duplicateStrategy" value="skip" class="mt-1">
                        <div>
                            <div class="font-medium">Skip duplikat</div>
                            <div class="text-xs text-gray-500">Data existing tidak diubah. Baru insert baris yang benar-benar baru.</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-2 p-3 border rounded-md cursor-pointer flex-1 dark:border-gray-700"
                           :class="'{{ $duplicateStrategy }}' === 'overwrite' ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10' : ''">
                        <input type="radio" wire:model.live="duplicateStrategy" value="overwrite" class="mt-1">
                        <div>
                            <div class="font-medium">Overwrite duplikat</div>
                            <div class="text-xs text-gray-500">Timpa data existing dengan yang di file. Hati-hati — data lama hilang.</div>
                        </div>
                    </label>
                </div>
            </x-filament::section>

            <x-filament::section collapsible collapsed>
                <x-slot name="heading">Detail Duplikat ({{ $stats['duplicate'] }} baris) — perbandingan existing vs baru</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead class="bg-gray-100 dark:bg-gray-800">
                            <tr>
                                <th class="p-2 text-left">Kode</th>
                                <th class="p-2 text-left">Field</th>
                                <th class="p-2 text-left">Existing (di sistem)</th>
                                <th class="p-2 text-left">Baru (di file)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->getRowsByStatus('duplicate') as $row)
                                @php
                                    $comparisons = [
                                        'Nama Produk' => [$row['existing_data']['name'] ?? '—', $row['name']],
                                        'Spesifikasi' => [$row['existing_data']['specification'] ?? '—', $row['specification'] ?? '—'],
                                        'Kategori' => [$row['existing_data']['category_name'] ?? '—', $row['category_name']],
                                        'NIE' => [$row['existing_data']['nie_number'] ?? '—', $row['nie_number'] ?? '—'],
                                        'QTY' => [$row['existing_data']['default_quantity'] ?? '—', $row['default_quantity'] ?? 1],
                                        'Kode Golongan' => [$row['existing_data']['product_group_code'] ?? '—', $row['product_group_code'] ?? '—'],
                                    ];
                                @endphp
                                @foreach ($comparisons as $field => [$oldVal, $newVal])
                                    <tr class="border-t border-gray-200 dark:border-gray-700 {{ (string)$oldVal !== (string)$newVal ? 'bg-warning-50 dark:bg-warning-500/10' : '' }}">
                                        @if ($loop->first)
                                            <td rowspan="{{ count($comparisons) }}" class="p-2 align-top font-mono">{{ $row['code'] }}</td>
                                        @endif
                                        <td class="p-2 text-gray-600 dark:text-gray-400">{{ $field }}</td>
                                        <td class="p-2">{{ $oldVal }}</td>
                                        <td class="p-2">{{ $newVal }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Invalid rows preview --}}
        @if ($stats['invalid'] > 0)
            <x-filament::section collapsible collapsed>
                <x-slot name="heading">Baris Invalid ({{ $stats['invalid'] }}) — akan di-skip</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-800">
                            <tr>
                                <th class="p-2 text-left">Sheet</th>
                                <th class="p-2 text-left">Baris</th>
                                <th class="p-2 text-left">Kode</th>
                                <th class="p-2 text-left">Nama</th>
                                <th class="p-2 text-left">Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->getRowsByStatus('invalid') as $row)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="p-2">{{ $row['category_name'] }}</td>
                                    <td class="p-2">{{ $row['row_number'] }}</td>
                                    <td class="p-2 font-mono">{{ $row['code'] ?? '—' }}</td>
                                    <td class="p-2">{{ $row['name'] ?? '—' }}</td>
                                    <td class="p-2 text-danger-600 dark:text-danger-400">{{ $row['error_reason'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Actions --}}
        <div class="flex gap-2">
            <x-filament::button
                wire:click="applyImport"
                wire:confirm="Yakin apply import? Perubahan tidak bisa di-undo otomatis."
                icon="heroicon-o-check"
                color="success">
                Konfirmasi Import ({{ $stats['new'] }} baru, {{ $duplicateStrategy === 'overwrite' ? $stats['duplicate'].' overwrite' : $stats['duplicate'].' skip' }})
            </x-filament::button>
            <x-filament::button wire:click="resetImport" color="gray" icon="heroicon-o-x-mark">
                Batal / Upload Ulang
            </x-filament::button>
        </div>
    @endif

    {{-- STEP 3: DONE --}}
    @if ($step === 'done' && $result)
        <x-filament::section>
            <x-slot name="heading">Import Selesai</x-slot>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="p-3 rounded bg-success-50 dark:bg-success-500/10">
                    <div class="text-xs text-gray-500">Ditambah</div>
                    <div class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $result['inserted'] }}</div>
                </div>
                <div class="p-3 rounded bg-info-50 dark:bg-info-500/10">
                    <div class="text-xs text-gray-500">Di-update</div>
                    <div class="text-2xl font-bold text-info-600 dark:text-info-400">{{ $result['updated'] }}</div>
                </div>
                <div class="p-3 rounded bg-gray-100 dark:bg-gray-800">
                    <div class="text-xs text-gray-500">Di-skip</div>
                    <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $result['skipped'] }}</div>
                </div>
                <div class="p-3 rounded bg-danger-50 dark:bg-danger-500/10">
                    <div class="text-xs text-gray-500">Invalid</div>
                    <div class="text-2xl font-bold text-danger-600 dark:text-danger-400">{{ $result['invalid'] }}</div>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <x-filament::button wire:click="resetImport" icon="heroicon-o-arrow-path">
                    Import Lagi
                </x-filament::button>
                <x-filament::link :href="route('filament.admin.resources.products.index')" icon="heroicon-o-arrow-top-right-on-square">
                    Lihat Daftar Produk
                </x-filament::link>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
