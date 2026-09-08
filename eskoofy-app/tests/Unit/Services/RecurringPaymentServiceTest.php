<?php

namespace Tests\Unit\Services;

use App\Services\RecurringPaymentService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecurringPaymentServiceTest extends TestCase
{
    #[Test]
    public function it_calculates_next_billing_date_for_monthly(): void
    {
        $from = Carbon::parse('2026-01-15');
        $next = RecurringPaymentService::calculateNextBillingDate('month', 1, $from);

        $this->assertEquals('2026-02-15', $next->toDateString());
    }

    #[Test]
    public function it_calculates_next_billing_date_with_frequency(): void
    {
        $from = Carbon::parse('2026-01-15');
        $next = RecurringPaymentService::calculateNextBillingDate('month', 3, $from);

        $this->assertEquals('2026-04-15', $next->toDateString());
    }

    #[Test]
    public function it_calculates_next_billing_date_for_day(): void
    {
        $from = Carbon::parse('2026-01-15');
        $next = RecurringPaymentService::calculateNextBillingDate('day', 7, $from);

        $this->assertEquals('2026-01-22', $next->toDateString());
    }

    #[Test]
    public function it_calculates_next_billing_date_for_year(): void
    {
        $from = Carbon::parse('2026-01-15');
        $next = RecurringPaymentService::calculateNextBillingDate('year', 1, $from);

        $this->assertEquals('2027-01-15', $next->toDateString());
    }

    #[Test]
    public function it_calculates_next_billing_date_for_week(): void
    {
        $from = Carbon::parse('2026-01-15');
        $next = RecurringPaymentService::calculateNextBillingDate('week', 2, $from);

        $this->assertEquals('2026-01-29', $next->toDateString());
    }

    #[Test]
    public function it_defaults_from_date_to_now(): void
    {
        $next = RecurringPaymentService::calculateNextBillingDate('month', 1);

        $this->assertEquals(now()->addMonth()->toDateString(), $next->toDateString());
    }

    #[Test]
    public function it_does_not_mutate_original_date(): void
    {
        $from = Carbon::parse('2026-01-15');
        RecurringPaymentService::calculateNextBillingDate('month', 1, $from);

        $this->assertEquals('2026-01-15', $from->toDateString());
    }

    #[Test]
    public function it_calculates_end_date_for_monthly_cycles(): void
    {
        $start = Carbon::parse('2026-01-15');
        $end = RecurringPaymentService::calculateEndDate('month', 1, 12, $start);

        $this->assertEquals('2027-01-15', $end->toDateString());
    }

    #[Test]
    public function it_calculates_end_date_with_frequency(): void
    {
        $start = Carbon::parse('2026-01-15');
        $end = RecurringPaymentService::calculateEndDate('month', 3, 4, $start);

        // 3 months * 4 cycles = 12 months
        $this->assertEquals('2027-01-15', $end->toDateString());
    }

    #[Test]
    public function it_calculates_end_date_for_weekly_cycles(): void
    {
        $start = Carbon::parse('2026-01-15');
        $end = RecurringPaymentService::calculateEndDate('week', 1, 4, $start);

        $this->assertEquals('2026-02-12', $end->toDateString());
    }

    #[Test]
    public function it_defaults_start_date_to_now_for_end_date(): void
    {
        $end = RecurringPaymentService::calculateEndDate('month', 1, 1);

        $this->assertEquals(now()->addMonth()->toDateString(), $end->toDateString());
    }

    #[Test]
    public function it_does_not_mutate_original_start_date_for_end_date(): void
    {
        $start = Carbon::parse('2026-01-15');
        RecurringPaymentService::calculateEndDate('month', 1, 12, $start);

        $this->assertEquals('2026-01-15', $start->toDateString());
    }
}
