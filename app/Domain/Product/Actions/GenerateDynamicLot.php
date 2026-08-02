<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\PrintSequence;
use Carbon\Carbon;

class GenerateDynamicLot
{
    /**
     * Generate dynamic LOT number based on product group code, year, month, and daily print sequence.
     * Format: {group_code}{year}{month}{sequence}
     * Example: 122607132
     *
     * @param Product $product
     * @return string
     */
    public function handle(Product $product, ?string $customSequence = null): string
    {
        $now = Carbon::now();

        // 1. Group code (strictly 2 digits)
        $rawGroupCode = (string) ($product->product_group_code ?? '00');
        $cleanGroupCode = preg_replace('/[^0-9]/', '', $rawGroupCode);
        $groupCode = str_pad(substr($cleanGroupCode, 0, 2), 2, '0', STR_PAD_LEFT);

        // 2. Year (2 digits)
        $year = $now->format('y');

        // 3. Month (2 digits)
        $month = $now->format('m');

        // 4. Daily Sequence (3 digits)
        if (!empty($customSequence)) {
            $cleanSeq = preg_replace('/[^0-9]/', '', (string) $customSequence);
            $sequencePadded = str_pad(substr($cleanSeq, 0, 3), 3, '0', STR_PAD_LEFT);
        } else {
            $sequence = $this->getTodaySequence();
            $sequencePadded = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
        }

        return $groupCode . $year . $month . $sequencePadded;
    }

    /**
     * Record or update today's sequence in database upon print confirmation.
     */
    public function recordPrintActivity(string $sequence): void
    {
        $cleanSeq = preg_replace('/[^0-9]/', '', (string) $sequence);
        $seqNum = (int) $cleanSeq;

        if ($seqNum > 0) {
            PrintSequence::updateOrCreate(
                ['date' => Carbon::today()],
                ['sequence_number' => $seqNum]
            );
        }
    }

    /**
     * Get current today sequence formatted as 3-digit string (e.g. '132').
     */
    public function getTodaySequenceString(): string
    {
        return str_pad((string) $this->getTodaySequence(), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get the sequence number for today.
     * If today's activity is not yet recorded, returns max sequence of current year + 1.
     */
    public function getTodaySequence(): int
    {
        $today = Carbon::today();

        $printSequence = PrintSequence::where('date', $today)->first();

        if ($printSequence) {
            return $printSequence->sequence_number;
        }

        return $this->getNextSequenceNumber();
    }

    private function getNextSequenceNumber(): int
    {
        $today = Carbon::today();
        $max = PrintSequence::whereYear('date', $today->year)->max('sequence_number');
        
        // Reset to 1 on a new year or if empty
        if (is_null($max)) {
            return 1;
        }

        return $max + 1;
    }
}
