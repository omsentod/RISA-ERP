<?php

namespace App\Domain\Stock\Exports;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Models\OutboundTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class RekapProdukKeluarExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private Carbon $from,
        private Carbon $until,
        private string $periodLabel,
    ) {}

    public function query()
    {
        $scope = fn (Builder $q) => $q
            ->whereBetween('scanned_at', [$this->from, $this->until])
            ->whereHas('transaction', fn (Builder $qq) => $qq->where('status', OutboundTransaction::STATUS_COMPLETED));

        return Product::query()
            ->select('products.*')
            ->whereHas('outboundItems', $scope)
            ->withSum(['outboundItems as total_qty_out' => $scope], 'quantity')
            ->withCount(['outboundItems as trx_count' => $scope])
            ->withMax(['outboundItems as last_out_at' => $scope], 'scanned_at')
            ->with('category')
            ->orderByDesc('total_qty_out');
    }

    public function headings(): array
    {
        return ['Kode', 'Nama Produk', 'Spesifikasi', 'Kategori', 'Total Qty Keluar', 'Jumlah Transaksi', 'Terakhir Keluar'];
    }

    public function map($row): array
    {
        return [
            $row->code,
            $row->name,
            $row->specification,
            $row->category?->name,
            (int) $row->total_qty_out,
            (int) $row->trx_count,
            $row->last_out_at ? Carbon::parse($row->last_out_at)->translatedFormat('d M Y H:i') : '',
        ];
    }

    public function title(): string
    {
        return 'Rekap ' . $this->periodLabel;
    }
}
