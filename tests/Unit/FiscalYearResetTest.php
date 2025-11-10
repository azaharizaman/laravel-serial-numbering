<?php

namespace AzahariZaman\ControlledNumber\Tests\Unit;

use AzahariZaman\ControlledNumber\Resets\FiscalYearReset;
use AzahariZaman\ControlledNumber\Tests\TestCase;

class FiscalYearResetTest extends TestCase
{
    /** @test */
    public function it_determines_fiscal_year_correctly()
    {
        $reset = new FiscalYearReset(4, 1); // April 1st start

        // March 31, 2024 should be FY2023
        $date1 = new \DateTime('2024-03-31');
        $fiscalYear1 = $this->callProtectedMethod($reset, 'getFiscalYear', [$date1]);
        $this->assertEquals(2023, $fiscalYear1);

        // April 1, 2024 should be FY2024
        $date2 = new \DateTime('2024-04-01');
        $fiscalYear2 = $this->callProtectedMethod($reset, 'getFiscalYear', [$date2]);
        $this->assertEquals(2024, $fiscalYear2);

        // December 31, 2024 should be FY2024
        $date3 = new \DateTime('2024-12-31');
        $fiscalYear3 = $this->callProtectedMethod($reset, 'getFiscalYear', [$date3]);
        $this->assertEquals(2024, $fiscalYear3);
    }

    /** @test */
    public function it_determines_reset_needed_across_fiscal_years()
    {
        $reset = new FiscalYearReset(4, 1); // April 1st start

        // Same fiscal year (both in FY2024) - no reset
        $lastReset = new \DateTime('2024-05-15');
        $now = new \DateTime('2024-06-15');
        $result = $this->shouldResetBetween($reset, $lastReset, $now);
        $this->assertFalse($result);

        // Different fiscal year - reset needed
        $lastReset = new \DateTime('2023-05-15'); // FY2023
        $now = new \DateTime('2024-05-15'); // FY2024
        $result = $this->shouldResetBetween($reset, $lastReset, $now);
        $this->assertTrue($result);
    }

    /**
     * Test helper to check reset between two dates.
     */
    protected function shouldResetBetween($reset, $lastReset, $currentDate)
    {
        $lastFiscalYear = $this->callProtectedMethod($reset, 'getFiscalYear', [$lastReset]);
        $currentFiscalYear = $this->callProtectedMethod($reset, 'getFiscalYear', [$currentDate]);
        
        return $lastFiscalYear !== $currentFiscalYear;
    }

    /** @test */
    public function it_generates_fiscal_year_code()
    {
        $reset = new FiscalYearReset(4, 1);

        $date = new \DateTime('2024-06-15'); // FY2024
        
        $shortCode = $reset->getFiscalYearCode($date, true);
        $this->assertEquals('FY2024', $shortCode);

        $longCode = $reset->getFiscalYearCode($date, false);
        $this->assertEquals('FY2024-25', $longCode);
    }

    /** @test */
    public function it_returns_description()
    {
        $reset = new FiscalYearReset(4, 1);
        $description = $reset->description();
        
        $this->assertStringContainsString('Fiscal Year', $description);
        $this->assertStringContainsString('April 1', $description);
    }

    /** @test */
    public function it_handles_null_last_reset()
    {
        $reset = new FiscalYearReset(4, 1);
        $result = $reset->shouldReset(null);
        
        $this->assertTrue($result);
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
