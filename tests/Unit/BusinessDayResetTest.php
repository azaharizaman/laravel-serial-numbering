<?php

namespace AzahariZaman\ControlledNumber\Tests\Unit;

use AzahariZaman\ControlledNumber\Resets\BusinessDayReset;
use AzahariZaman\ControlledNumber\Tests\TestCase;

class BusinessDayResetTest extends TestCase
{
    /** @test */
    public function it_skips_weekends()
    {
        $reset = new BusinessDayReset([0, 6]); // Skip Sunday and Saturday

        // Friday to Monday (same week, different business days)
        $lastReset = new \DateTime('2024-11-08'); // Friday
        // Set current time to Monday (simulate)
        $result = $this->shouldResetBetween($reset, $lastReset, new \DateTime('2024-11-11')); // Monday
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_identifies_business_days()
    {
        $reset = new BusinessDayReset([0, 6]);

        // Monday is a business day
        $monday = new \DateTime('2024-11-11');
        $isNonBusiness = $this->callProtectedMethod($reset, 'isNonBusinessDay', [$monday]);
        $this->assertFalse($isNonBusiness);

        // Saturday is not a business day
        $saturday = new \DateTime('2024-11-09');
        $isNonBusiness = $this->callProtectedMethod($reset, 'isNonBusinessDay', [$saturday]);
        $this->assertTrue($isNonBusiness);
    }

    /** @test */
    public function it_handles_holidays()
    {
        $holidays = ['2024-12-25', '2024-01-01'];
        $reset = new BusinessDayReset([0, 6], $holidays);

        // Christmas is a holiday
        $christmas = new \DateTime('2024-12-25');
        $isNonBusiness = $this->callProtectedMethod($reset, 'isNonBusinessDay', [$christmas]);
        $this->assertTrue($isNonBusiness);
    }

    /** @test */
    public function it_returns_description()
    {
        $reset = new BusinessDayReset([0, 6]);
        $description = $reset->description();
        
        $this->assertStringContainsString('Business Days', $description);
        $this->assertStringContainsString('Sunday', $description);
        $this->assertStringContainsString('Saturday', $description);
    }

    /** @test */
    public function it_handles_null_last_reset()
    {
        $reset = new BusinessDayReset();
        $result = $reset->shouldReset(null);
        
        $this->assertTrue($result);
    }

    /**
     * Test helper to check reset between two dates.
     */
    protected function shouldResetBetween($reset, $lastReset, $currentDate)
    {
        $lastBusinessDay = $this->callProtectedMethod($reset, 'getLastBusinessDay', [$lastReset]);
        $currentBusinessDay = $this->callProtectedMethod($reset, 'getLastBusinessDay', [$currentDate]);
        
        return $lastBusinessDay->format('Y-m-d') !== $currentBusinessDay->format('Y-m-d');
    }

    /**
     * Call protected method for testing.
     */
    protected function callProtectedMethod($object, $method, array $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $args);
    }
}
