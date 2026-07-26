@php
    use Illuminate\Support\Carbon;
    $viewUrl = fn ($id) => \App\Filament\Resources\OutboundTransactionResource::getUrl('view', ['record' => $id]);
@endphp

<div class="space-y-4">
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
        <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
            <div>
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Kode</div>
                <div class="mt-1 font-mono font-semibold text-gray-950 dark:text-white">{{ $product->code }}</div>
            </div>
            <div>
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Qty Keluar</div>
                <div class="mt-1 text-lg font-bold text-primary-600 dark:text-primary-400">{{ number_format($totalQty) }} unit</div>
            </div>
            <div>
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Jumlah Transaksi</div>
                <div class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $items->count() }} surat jalan</div>
            </div>
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
            Tidak ada jejak barang keluar untuk periode {{ $periodLabel }}.
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr class="text-left">
                            <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">No. Surat Jalan</th>
                            <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Tanggal SJ</th>
                            <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Waktu Scan</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Qty</th>
                            <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Tujuan</th>
                            <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Dibuat oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($items as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3">
                                    <a href="{{ $viewUrl($item->tx_id) }}" class="font-mono font-medium text-primary-600 hover:underline dark:text-primary-400">
                                        {{ $item->doc_no }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ Carbon::parse($item->doc_date)->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ Carbon::parse($item->scanned_at)->translatedFormat('d M Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center rounded-md bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-500/20 dark:text-primary-300">
                                        {{ $item->quantity }} unit
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ $item->destination ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ $item->creator_name ?: '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-white/5">
                        <tr class="font-semibold">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200" colspan="3">Total</td>
                            <td class="px-4 py-3 text-right">
                                <span class="inline-flex items-center rounded-md bg-primary-600 px-2 py-0.5 text-xs font-bold text-white">
                                    {{ number_format($totalQty) }} unit
                                </span>
                            </td>
                            <td class="px-4 py-3" colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
