<?php

namespace App\Domain\Stock\Actions;

use App\Domain\Stock\Models\OutboundTransaction;

class StartOutboundSession
{
    public function __construct(private GenerateOutboundDocNo $generateDocNo) {}

    public function handle(?int $userId = null): OutboundTransaction
    {
        return OutboundTransaction::create([
            'doc_no' => $this->generateDocNo->handle(),
            'doc_date' => now()->toDateString(),
            'status' => OutboundTransaction::STATUS_DRAFT,
            'started_at' => now(),
            'total_qty' => 0,
            'created_by' => $userId ?? auth()->id(),
        ]);
    }
}
