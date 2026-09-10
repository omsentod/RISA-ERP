<?php

namespace App\Domain\Stock\Actions;

use App\Domain\Stock\Models\OutboundTransaction;
use Illuminate\Support\Carbon;

class GenerateOutboundDocNo
{
    public function handle(?Carbon $date = null): string
    {
        $date ??= now();
        $prefix = 'SJ-' . $date->format('Ymd');

        // Urutan berjalan per tahun (reset ke 001 tiap awal tahun).
        $lastOfYear = OutboundTransaction::query()
            ->withTrashed()
            ->whereYear('doc_date', $date->year)
            ->where('doc_no', 'like', 'SJ-%')
            ->orderByDesc('doc_no')
            ->value('doc_no');

        $seq = 1;
        if ($lastOfYear !== null) {
            $lastSeq = (int) substr($lastOfYear, strrpos($lastOfYear, '-') + 1);
            $seq = $lastSeq + 1;
        }

        return sprintf('%s-%03d', $prefix, $seq);
    }
}
