@php
    use App\Domain\Import\Data\ProductImportRow;
    $result = $result ?? null;
@endphp

@if ($result === null)
    <div class="text-sm text-gray-500 dark:text-gray-400">Membaca file…</div>
@elseif (is_array($result) && isset($result['__error']))
    <div class="text-sm text-danger-600 dark:text-danger-400">Gagal membaca file: {{ $result['__error'] }}</div>
@else
    @php
        $rows = collect($result);
        $new = $rows->where('status', ProductImportRow::STATUS_NEW);
        $dup = $rows->where('status', ProductImportRow::STATUS_DUPLICATE);
        $invalid = $rows->where('status', ProductImportRow::STATUS_INVALID);
    @endphp

    <div class="flex flex-wrap gap-2 text-xs font-medium">
        <span class="rounded-md bg-success-50 px-2 py-1 text-success-700 dark:bg-success-500/10 dark:text-success-400">{{ $new->count() }} baru</span>
        <span class="rounded-md bg-warning-50 px-2 py-1 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">{{ $dup->count() }} sama persis</span>
        <span class="rounded-md bg-danger-50 px-2 py-1 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">{{ $invalid->count() }} invalid</span>
    </div>

    @if ($dup->isNotEmpty())
        <div class="mt-3">
            <div class="mb-1 text-xs font-semibold text-gray-600 dark:text-gray-300">
                Sama persis dengan produk existing — di-skip jika pilih "Skip yang sama persis":
            </div>
            <div class="max-h-40 overflow-y-auto rounded-md border border-gray-200 dark:border-white/10">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        <tr>
                            <th class="p-1.5 text-left font-medium">Kode</th>
                            <th class="p-1.5 text-left font-medium">Nama</th>
                            <th class="p-1.5 text-left font-medium">Kategori</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dup as $row)
                            <tr class="border-t border-gray-100 dark:border-white/5">
                                <td class="p-1.5 font-mono">{{ $row->code }}</td>
                                <td class="p-1.5">{{ $row->name }}</td>
                                <td class="p-1.5 text-gray-500 dark:text-gray-400">{{ $row->categoryName }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($invalid->isNotEmpty())
        <div class="mt-2 text-xs text-danger-600 dark:text-danger-400">
            {{ $invalid->count() }} baris invalid (Kode/Nama kosong) akan dilewati.
        </div>
    @endif
@endif
