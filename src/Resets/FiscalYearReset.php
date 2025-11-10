<?php

namespace AzahariZaman\ControlledNumber\Resets;

use AzahariZaman\ControlledNumber\Contracts\ResetStrategyInterface;

class FiscalYearReset implements ResetStrategyInterface
{
    /**
     * Starting month of the fiscal year (1-12).
     */
    protected int $startMonth;

    /**
     * Starting day of the fiscal year (1-31).
     */
    protected int $startDay;

    /**
     * Create a new fiscal year reset strategy.
     *
     * @param int $startMonth Starting month (1=January, 4=April, etc.)
     * @param int $startDay Starting day of the month
     */
    public function __construct(int $startMonth = 4, int $startDay = 1)
    {
        $this->startMonth = max(1, min(12, $startMonth));
        $this->startDay = max(1, min(31, $startDay));
    }

    /**
     * Determine if reset should occur based on fiscal year boundary.
     */
    public function shouldReset(?\DateTime $lastReset, int $interval = 1): bool
    {
        if ($lastReset === null) {
            return true;
        }

        $now = new \DateTime();
        
        // Calculate fiscal year for last reset
        $lastFiscalYear = $this->getFiscalYear($lastReset);
        
        // Calculate fiscal year for current date
        $currentFiscalYear = $this->getFiscalYear($now);

        return $currentFiscalYear !== $lastFiscalYear;
    }

    /**
     * Get the fiscal year for a given date.
     *
     * @param \DateTime $date
     * @return int
     */
    protected function getFiscalYear(\DateTime $date): int
    {
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');
        $day = (int) $date->format('j');

        // If we're before the fiscal year start, we're in the previous fiscal year
        if ($month < $this->startMonth || 
            ($month === $this->startMonth && $day < $this->startDay)) {
            return $year - 1;
        }

        return $year;
    }

    /**
     * Get the fiscal year code (e.g., "FY2024" or "FY2024-25").
     *
     * @param \DateTime|null $date
     * @param bool $shortFormat If true, returns "FY2024", else "FY2024-25"
     * @return string
     */
    public function getFiscalYearCode(?\DateTime $date = null, bool $shortFormat = false): string
    {
        $date = $date ?? new \DateTime();
        $fiscalYear = $this->getFiscalYear($date);

        if ($shortFormat) {
            return 'FY' . $fiscalYear;
        }

        // For fiscal years not starting in January, show range
        if ($this->startMonth !== 1) {
            $nextYear = substr((string)($fiscalYear + 1), -2);
            return 'FY' . $fiscalYear . '-' . $nextYear;
        }

        return 'FY' . $fiscalYear;
    }

    /**
     * Get human-readable description.
     */
    public function description(): string
    {
        $monthName = \DateTime::createFromFormat('!m', (string) $this->startMonth)->format('F');
        return "Fiscal Year (starts {$monthName} {$this->startDay})";
    }
}
