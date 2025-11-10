<?php

namespace AzahariZaman\ControlledNumber\Contracts;

interface ResetStrategyInterface
{
    /**
     * Determine if a reset should occur based on the last reset timestamp.
     *
     * @param \DateTime|null $lastReset
     * @param int $interval
     * @return bool
     */
    public function shouldReset(?\DateTime $lastReset, int $interval = 1): bool;

    /**
     * Get a human-readable description of this reset strategy.
     *
     * @return string
     */
    public function description(): string;
}
