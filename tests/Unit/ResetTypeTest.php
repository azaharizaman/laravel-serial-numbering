<?php

namespace AzahariZaman\ControlledNumber\Tests\Unit;

use AzahariZaman\ControlledNumber\Enums\ResetType;
use PHPUnit\Framework\TestCase;

class ResetTypeTest extends TestCase
{
    public function test_label_returns_human_readable_value(): void
    {
        $this->assertSame('Never', ResetType::NEVER->label());
        $this->assertSame('Daily', ResetType::DAILY->label());
        $this->assertSame('Weekly', ResetType::WEEKLY->label());
        $this->assertSame('Monthly', ResetType::MONTHLY->label());
        $this->assertSame('Yearly', ResetType::YEARLY->label());
        $this->assertSame('Custom Interval', ResetType::INTERVAL->label());
    }

    public function test_never_reset_type_never_resets(): void
    {
        $this->assertFalse(ResetType::NEVER->shouldReset(null));
        $this->assertFalse(ResetType::NEVER->shouldReset(new \DateTime('-10 years')));
    }

    public function test_daily_reset_type_respects_day_boundary(): void
    {
        $sameDay = new \DateTime();
        $previousDay = new \DateTime('-1 day');

        $this->assertFalse(ResetType::DAILY->shouldReset(clone $sameDay));
        $this->assertTrue(ResetType::DAILY->shouldReset($previousDay));
    }

    public function test_weekly_and_monthly_reset_types(): void
    {
        $previousWeek = new \DateTime('-1 week');
        $previousMonth = new \DateTime('-1 month');

        $this->assertTrue(ResetType::WEEKLY->shouldReset($previousWeek));
        $this->assertTrue(ResetType::MONTHLY->shouldReset($previousMonth));
    }

    public function test_yearly_reset_type_detects_new_year(): void
    {
        $previousYear = new \DateTime('-1 year');
        $this->assertTrue(ResetType::YEARLY->shouldReset($previousYear));
    }

    public function test_interval_reset_type_uses_interval_days(): void
    {
        $recent = new \DateTime('-1 day');
        $older = new \DateTime('-3 days');

        $this->assertFalse(ResetType::INTERVAL->shouldReset($recent, 2));
        $this->assertTrue(ResetType::INTERVAL->shouldReset($older, 2));
    }
}
