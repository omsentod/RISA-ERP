<x-filament-panels::page>
    @php
        $items = $this->transaction->items()->with('product')->orderByDesc('scanned_at')->get();
    @endphp

    {{-- HEADER INFO --}}
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->transaction->doc_no }}
            <span class="ml-2 inline-flex items-center gap-1 rounded-md bg-warning-100 dark:bg-warning-500/20 px-2 py-0.5 text-xs font-medium text-warning-800 dark:text-warning-300">
                Draft
            </span>
        </x-slot>
        <x-slot name="description">
            Dimulai {{ $this->transaction->started_at?->diffForHumans() }} · {{ $this->transaction->doc_date?->format('d M Y') }}
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tujuan (RS / customer)</label>
                <input type="text" wire:model.blur="destination" wire:change="updateHeaderInfo"
                    placeholder="Contoh: RSUD Dr. Soetomo"
                    class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Catatan</label>
                <input type="text" wire:model.blur="notes" wire:change="updateHeaderInfo"
                    placeholder="Catatan tambahan"
                    class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
            </div>
        </div>
    </x-filament::section>

    {{-- SCAN INPUT --}}
    <x-filament::section>
        <x-slot name="heading">Scan Barcode</x-slot>
        <x-slot name="description">Arahkan scanner gun ke barcode produk, atau ketik manual + Enter. Field auto-clear setiap scan.</x-slot>

        <form wire:submit.prevent="submitScan" class="flex gap-2" x-data x-init="$refs.scanInput?.focus()">
            <input type="text"
                x-ref="scanInput"
                wire:model="scanInput"
                autofocus
                autocomplete="off"
                placeholder="Scan / ketik kode produk lalu Enter"
                class="fi-input block flex-1 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-base font-mono py-2.5"
                @keydown.enter.prevent="$wire.submitScan().then(() => $refs.scanInput.focus())">
            <x-filament::button type="submit" icon="heroicon-o-plus" size="lg">Tambah</x-filament::button>
        </form>
    </x-filament::section>

    {{-- ITEMS TABLE --}}
    <x-filament::section>
        <x-slot name="heading">
            Daftar Item Scan
            <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                {{ $items->count() }} jenis · {{ $this->transaction->total_qty }} unit total
            </span>
        </x-slot>

        @if ($items->isEmpty())
            <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                Belum ada item. Mulai scan barcode di atas.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="p-2 text-left font-medium">Kode</th>
                            <th class="p-2 text-left font-medium">Nama Produk</th>
                            <th class="p-2 text-center font-medium w-32">Qty</th>
                            <th class="p-2 text-right font-medium w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="p-2 font-mono font-medium">{{ $item->product->code }}</td>
                                <td class="p-2">{{ $item->product->name }}</td>
                                <td class="p-2 text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <button wire:click="decrementItemQty({{ $item->id }})"
                                            class="w-7 h-7 flex items-center justify-center rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300"
                                            title="Kurangi">−</button>
                                        <span class="min-w-[2rem] text-center font-semibold">{{ $item->quantity }}</span>
                                        <button wire:click="incrementItemQty({{ $item->id }})"
                                            class="w-7 h-7 flex items-center justify-center rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300"
                                            title="Tambah">+</button>
                                    </div>
                                </td>
                                <td class="p-2 text-right">
                                    <button wire:click="removeItem({{ $item->id }})"
                                        wire:confirm="Hapus {{ $item->product->code }} dari daftar?"
                                        class="text-danger-600 dark:text-danger-400 hover:underline text-xs">Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{-- FOOTER ACTIONS --}}
    <div class="flex gap-2 justify-end">
        <x-filament::button
            wire:click="cancelSession"
            wire:confirm="Yakin batalkan sesi? Semua item terscan akan hilang dari draft ini."
            color="danger"
            icon="heroicon-o-x-mark">
            Batalkan Sesi
        </x-filament::button>
        <x-filament::button
            wire:click="completeSession"
            color="success"
            icon="heroicon-o-check-circle"
            size="lg">
            Selesai — Simpan Surat Jalan
        </x-filament::button>
    </div>
</x-filament-panels::page>
