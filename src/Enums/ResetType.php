<?php

namespace AzahariZaman\ControlledNumber\Enums;

enum ResetType: string
{
    case NEVER = 'never';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    case INTERVAL = 'interval';
    case CUSTOM = 'custom';

    /**
     * Get the human-readable label for the reset type.
     */
    public function label(): string
    {
        return match($this) {
            self::NEVER => 'Never',
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
            self::YEARLY => 'Yearly',
            self::INTERVAL => 'Custom Interval',
            self::CUSTOM => 'Custom Strategy',
        };
    }

    /**
     * Determine if reset is needed based on last reset timestamp.
     */
    public function shouldReset(?\DateTime $lastReset, int $interval = 1): bool
    {
        if ($this === self::NEVER) {
            return false;
        }

        if ($this === self::CUSTOM) {
            // Custom reset logic handled by SerialSequence model
            return false;
        }

        if ($lastReset === null) {
            return true;
        }

        $now = new \DateTime();
        
        return match($this) {
            self::DAILY => $lastReset->format('Y-m-d') !== $now->format('Y-m-d'),
            self::WEEKLY => $lastReset->format('Y-W') !== $now->format('Y-W'),
            self::MONTHLY => $lastReset->format('Y-m') !== $now->format('Y-m'),
            self::YEARLY => $lastReset->format('Y') !== $now->format('Y'),
            self::INTERVAL => $now->getTimestamp() - $lastReset->getTimestamp() >= ($interval * 86400),
            self::NEVER, self::CUSTOM => false,
        };
    }
}
