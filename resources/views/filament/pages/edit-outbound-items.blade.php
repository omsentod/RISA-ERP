<x-filament-panels::page>
    {{-- HEADER INFO --}}
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->transaction->doc_no }}
            @if ($this->transaction->isCompleted())
                <span class="ml-2 inline-flex items-center gap-1 rounded-md bg-success-100 dark:bg-success-500/20 px-2 py-0.5 text-xs font-medium text-success-800 dark:text-success-300">
                    Selesai
                </span>
            @elseif ($this->transaction->isDraft())
                <span class="ml-2 inline-flex items-center gap-1 rounded-md bg-warning-100 dark:bg-warning-500/20 px-2 py-0.5 text-xs font-medium text-warning-800 dark:text-warning-300">
                    Draft
                </span>
            @endif
        </x-slot>
        <x-slot name="description">
            Mode edit item · {{ $this->transaction->doc_date?->format('d M Y') }}
            @if ($this->transaction->destination)
                · Tujuan: {{ $this->transaction->destination }}
            @endif
        </x-slot>
    </x-filament::section>

    {{-- ADD ITEM SECTION --}}
    <x-filament::section>
        <x-slot name="heading">Tambah Item Baru</x-slot>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-5">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Produk</label>
                <select wire:model="addProductId"
                    class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                    <option value="">-- Pilih Produk --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->code }} — {{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Qty</label>
                <input type="number" wire:model="addQty" min="1"
                    class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">No. Lot (opsional)</label>
                <input type="text" wire:model="addLotNumber" placeholder="Contoh: 122609001"
                    class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm font-mono">
            </div>
            <div class="md:col-span-2">
                <button wire:click="addItem" type="button"
                    class="w-full inline-flex items-center justify-center gap-1 rounded-lg bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium px-3 py-2 transition">
                    <x-heroicon-m-plus class="w-4 h-4"/>
                    Tambah
                </button>
            </div>
        </div>
    </x-filament::section>

    {{-- ITEMS TABLE --}}
    <x-filament::section>
        <x-slot name="heading">
            Daftar Item
            <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                {{ $items->count() }} jenis · {{ $this->transaction->total_qty }} unit total
            </span>
        </x-slot>

        @if ($items->isEmpty())
            <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                Belum ada item.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="p-2.5 text-left font-medium text-gray-600 dark:text-gray-400">Kode</th>
                            <th class="p-2.5 text-left font-medium text-gray-600 dark:text-gray-400">Nama Produk</th>
                            <th class="p-2.5 text-left font-medium text-gray-600 dark:text-gray-400 w-44">No. Lot</th>
                            <th class="p-2.5 text-center font-medium text-gray-600 dark:text-gray-400 w-36">Qty</th>
                            <th class="p-2.5 text-right font-medium text-gray-600 dark:text-gray-400 w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr wire:key="edit-item-{{ $item->id }}" class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="p-2.5 font-mono font-medium">
                                    {{ $item->product->code }}
                                    @if ($item->product->is_custom)
                                        <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 font-sans font-normal">Custom</span>
                                    @endif
                                </td>
                                <td class="p-2.5 text-gray-700 dark:text-gray-300">{{ $item->product->name }}</td>
                                <td class="p-2.5">
                                    <input type="text"
                                        value="{{ $item->lot_number }}"
                                        wire:change="updateItemLot({{ $item->id }}, $event.target.value)"
                                        placeholder="Masukkan lot"
                                        class="fi-input font-mono text-xs block w-full px-2 py-1 rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                </td>
                                <td class="p-2.5 text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <button wire:click="decrementItemQty({{ $item->id }})"
                                            class="w-7 h-7 flex items-center justify-center rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition"
                                            title="Kurangi">−</button>
                                        <input type="number"
                                            value="{{ $item->quantity }}"
                                            wire:change="updateItemQty({{ $item->id }}, $event.target.value)"
                                            class="w-14 text-center font-semibold text-sm rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:border-primary-500 focus:ring-primary-500"
                                            min="0">
                                        <button wire:click="incrementItemQty({{ $item->id }})"
                                            class="w-7 h-7 flex items-center justify-center rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition"
                                            title="Tambah">+</button>
                                    </div>
                                </td>
                                <td class="p-2.5 text-right">
                                    <button wire:click="removeItem({{ $item->id }})"
                                        wire:confirm="Hapus {{ $item->product->code }} dari daftar?"
                                        class="text-danger-600 dark:text-danger-400 hover:underline text-xs font-medium">Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
