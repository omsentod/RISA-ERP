<?php

namespace Tests\Feature\Domain\Stock\Actions;

use App\Domain\Stock\Actions\GenerateOutboundDocNo;
use App\Domain\Stock\Models\OutboundTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GenerateOutboundDocNoTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function makeTx(string $docNo, ?string $date = null): OutboundTransaction
    {
        return OutboundTransaction::create([
            'doc_no' => $docNo,
            'doc_date' => $date ?? now()->toDateString(),
            'status' => OutboundTransaction::STATUS_DRAFT,
            'total_qty' => 0,
        ]);
    }

    public function test_returns_first_sequence_when_no_record_exists_for_date(): void
    {
        Carbon::setTestNow('2026-07-26 10:00:00');

        $docNo = app(GenerateOutboundDocNo::class)->handle();

        $this->assertSame('SJ-20260726-001', $docNo);
    }

    public function test_increments_sequence_from_latest_same_day_record(): void
    {
        Carbon::setTestNow('2026-07-26 10:00:00');
        $this->makeTx('SJ-20260726-001');
        $this->makeTx('SJ-20260726-002');

        $docNo = app(GenerateOutboundDocNo::class)->handle();

        $this->assertSame('SJ-20260726-003', $docNo);
    }

    public function test_continues_sequence_across_days_within_same_year(): void
    {
        $this->makeTx('SJ-20260725-005', '2026-07-25');
        Carbon::setTestNow('2026-07-26 10:00:00');

        $docNo = app(GenerateOutboundDocNo::class)->handle();

        $this->assertSame('SJ-20260726-006', $docNo);
    }

    public function test_resets_to_001_on_new_year(): void
    {
        $this->makeTx('SJ-20261230-042', '2026-12-30');
        Carbon::setTestNow('2027-01-05 10:00:00');

        $docNo = app(GenerateOutboundDocNo::class)->handle();

        $this->assertSame('SJ-20270105-001', $docNo);
    }

    public function test_counts_soft_deleted_records_to_avoid_unique_constraint_conflict(): void
    {
        Carbon::setTestNow('2026-07-26 10:00:00');
        $this->makeTx('SJ-20260726-001');
        $this->makeTx('SJ-20260726-002');
        $this->makeTx('SJ-20260726-003')->delete();

        $docNo = app(GenerateOutboundDocNo::class)->handle();

        $this->assertSame('SJ-20260726-004', $docNo);
    }

    public function test_respects_explicit_date_argument(): void
    {
        $docNo = app(GenerateOutboundDocNo::class)->handle(Carbon::parse('2026-01-15'));

        $this->assertSame('SJ-20260115-001', $docNo);
    }
}
