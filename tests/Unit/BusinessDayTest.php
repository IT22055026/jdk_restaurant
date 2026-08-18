<?php

namespace Tests\Unit;

use App\Support\BusinessDay;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class BusinessDayTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a_moment_before_the_cutoff_belongs_to_the_previous_business_date()
    {
        $this->assertEquals('2026-07-28', BusinessDay::of('2026-07-29 01:00:00')->toDateString());
        $this->assertEquals('2026-07-28', BusinessDay::of('2026-07-29 05:59:59')->toDateString());
    }

    public function test_a_moment_at_or_after_the_cutoff_belongs_to_that_calendar_date()
    {
        $this->assertEquals('2026-07-29', BusinessDay::of('2026-07-29 06:00:00')->toDateString());
        $this->assertEquals('2026-07-29', BusinessDay::of('2026-07-29 20:00:00')->toDateString());
        $this->assertEquals('2026-07-29', BusinessDay::of('2026-07-29 23:59:59')->toDateString());
    }

    public function test_today_reflects_the_current_business_date_not_the_calendar_date()
    {
        Carbon::setTestNow('2026-07-30 02:30:00');
        $this->assertEquals('2026-07-29', BusinessDay::today()->toDateString());

        Carbon::setTestNow('2026-07-30 09:00:00');
        $this->assertEquals('2026-07-30', BusinessDay::today()->toDateString());
    }

    public function test_bounds_for_a_business_date_span_6am_through_559_the_next_day()
    {
        [$start, $end] = BusinessDay::boundsFor('2026-07-29');

        $this->assertEquals('2026-07-29 06:00:00', $start->toDateTimeString());
        $this->assertEquals('2026-07-30 05:59:59', $end->toDateTimeString());
    }

    public function test_bounds_between_spans_the_full_range_across_multiple_days()
    {
        [$start, $end] = BusinessDay::boundsBetween('2026-07-29', '2026-07-31');

        $this->assertEquals('2026-07-29 06:00:00', $start->toDateTimeString());
        $this->assertEquals('2026-08-01 05:59:59', $end->toDateTimeString());
    }

    public function test_month_bounds_start_the_1st_at_6am_and_end_the_1st_of_next_month_at_559()
    {
        [$start, $end] = BusinessDay::monthBoundsFor('2026-07-15');

        $this->assertEquals('2026-07-01 06:00:00', $start->toDateTimeString());
        $this->assertEquals('2026-08-01 05:59:59', $end->toDateTimeString());
    }
}
