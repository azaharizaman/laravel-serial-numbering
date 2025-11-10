<?php

namespace AzahariZaman\ControlledNumber\Resets;

use AzahariZaman\ControlledNumber\Contracts\ResetStrategyInterface;

class BusinessDayReset implements ResetStrategyInterface
{
    /**
     * Days to skip (0=Sunday, 6=Saturday).
     */
    protected array $skipDays;

    /**
     * Holidays to skip (Y-m-d format).
     */
    protected array $holidays;

    /**
     * Create a new business day reset strategy.
     *
     * @param array $skipDays Days of week to skip (0=Sunday, 6=Saturday)
     * @param array $holidays Array of holiday dates in Y-m-d format
     */
    public function __construct(array $skipDays = [0, 6], array $holidays = [])
    {
        $this->skipDays = $skipDays;
        $this->holidays = $holidays;
    }

    /**
     * Determine if reset should occur (skips weekends and holidays).
     */
    public function shouldReset(?\DateTime $lastReset, int $interval = 1): bool
    {
        if ($lastReset === null) {
            return true;
        }

        $now = new \DateTime();

        // Get the last business day from lastReset
        $lastBusinessDay = $this->getLastBusinessDay($lastReset);
        
        // Get current business day
        $currentBusinessDay = $this->getLastBusinessDay($now);

        return $lastBusinessDay->format('Y-m-d') !== $currentBusinessDay->format('Y-m-d');
    }

    /**
     * Get the most recent business day from a given date.
     *
     * @param \DateTime $date
     * @return \DateTime
     */
    protected function getLastBusinessDay(\DateTime $date): \DateTime
    {
        $checkDate = clone $date;

        // Walk backwards until we find a business day
        while ($this->isNonBusinessDay($checkDate)) {
            $checkDate->modify('-1 day');
        }

        return $checkDate;
    }

    /**
     * Check if a date is a non-business day.
     *
     * @param \DateTime $date
     * @return bool
     */
    protected function isNonBusinessDay(\DateTime $date): bool
    {
        // Check if it's a skip day (weekend)
        if (in_array((int) $date->format('w'), $this->skipDays)) {
            return true;
        }

        // Check if it's a holiday
        if (in_array($date->format('Y-m-d'), $this->holidays)) {
            return true;
        }

        return false;
    }

    /**
     * Get human-readable description.
     */
    public function description(): string
    {
        $skipDayNames = array_map(function($day) {
            return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$day];
        }, $this->skipDays);

        $skipText = implode(', ', $skipDayNames);
        $holidayCount = count($this->holidays);

        return "Business Days (skips {$skipText}" . 
               ($holidayCount > 0 ? " and {$holidayCount} holidays" : "") . ")";
    }
}
