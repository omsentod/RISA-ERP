<?php

namespace Tests\Unit\Domain\Shared;

use App\Domain\Shared\Enums\DateRangePreset;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DateRangePresetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-26 14:30:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_today_returns_start_and_end_of_today(): void
    {
        [$from, $until] = DateRangePreset::Today->range();

        $this->assertSame('2026-07-26 00:00:00', $from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-26 23:59:59', $until->format('Y-m-d H:i:s'));
    }

    public function test_yesterday_returns_prior_day(): void
    {
        [$from, $until] = DateRangePreset::Yesterday->range();

        $this->assertSame('2026-07-25', $from->toDateString());
        $this->assertSame('2026-07-25', $until->toDateString());
    }

    public function test_last_7_days_spans_a_week_including_today(): void
    {
        [$from, $until] = DateRangePreset::Last7Days->range();

        $this->assertSame('2026-07-20 00:00:00', $from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-26 23:59:59', $until->format('Y-m-d H:i:s'));
    }

    public function test_this_month_spans_month(): void
    {
        [$from, $until] = DateRangePreset::ThisMonth->range();

        $this->assertSame('2026-07-01 00:00:00', $from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-31 23:59:59', $until->format('Y-m-d H:i:s'));
    }

    public function test_last_month_spans_previous_month(): void
    {
        [$from, $until] = DateRangePreset::LastMonth->range();

        $this->assertSame('2026-06-01', $from->toDateString());
        $this->assertSame('2026-06-30', $until->toDateString());
    }

    public function test_this_year_spans_year(): void
    {
        [$from, $until] = DateRangePreset::ThisYear->range();

        $this->assertSame('2026-01-01', $from->toDateString());
        $this->assertSame('2026-12-31', $until->toDateString());
    }

    public function test_last_year_spans_previous_year(): void
    {
        [$from, $until] = DateRangePreset::LastYear->range();

        $this->assertSame('2025-01-01', $from->toDateString());
        $this->assertSame('2025-12-31', $until->toDateString());
    }

    public function test_custom_uses_supplied_dates(): void
    {
        [$from, $until] = DateRangePreset::Custom->range('2026-03-01', '2026-03-15');

        $this->assertSame('2026-03-01 00:00:00', $from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-15 23:59:59', $until->format('Y-m-d H:i:s'));
    }

    public function test_custom_falls_back_to_last_month_range_when_no_dates(): void
    {
        [$from, $until] = DateRangePreset::Custom->range();

        // Fallback: 1 bulan lalu s/d hari ini
        $this->assertSame('2026-06-26', $from->toDateString());
        $this->assertSame('2026-07-26', $until->toDateString());
    }

    public function test_options_returns_all_cases_with_labels(): void
    {
        $options = DateRangePreset::options();

        $this->assertArrayHasKey('today', $options);
        $this->assertArrayHasKey('custom', $options);
        $this->assertCount(8, $options);
        $this->assertSame('Hari ini', $options['today']);
        $this->assertSame('Bulan ini', $options['this_month']);
    }
}
