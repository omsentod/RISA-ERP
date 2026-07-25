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

        $lastToday = OutboundTransaction::query()
            ->withTrashed()
            ->whereDate('doc_date', $date->toDateString())
            ->where('doc_no', 'like', "{$prefix}-%")
            ->orderByDesc('doc_no')
            ->value('doc_no');

        $seq = 1;
        if ($lastToday !== null) {
            $lastSeq = (int) substr($lastToday, strrpos($lastToday, '-') + 1);
            $seq = $lastSeq + 1;
        }

        return sprintf('%s-%03d', $prefix, $seq);
    }
}
