<?php

namespace AzahariZaman\ControlledNumber\Behaviors;

use AzahariZaman\ControlledNumber\Enums\ResetType;

trait Resettable
{
    /**
     * Check if the sequence should be reset.
     */
    public function shouldReset(): bool
    {
        if (!isset($this->reset_type)) {
            return false;
        }

        $resetType = $this->reset_type instanceof ResetType 
            ? $this->reset_type 
            : ResetType::from($this->reset_type);

        return $resetType->shouldReset(
            $this->last_reset_at,
            $this->reset_interval ?? 1
        );
    }

    /**
     * Reset the sequence to a specific value.
     */
    public function resetTo(int $value): void
    {
        $this->update([
            'current_number' => $value,
            'last_reset_at' => now(),
        ]);
    }

    /**
     * Reset the sequence to the default start value.
     */
    public function resetToDefault(): void
    {
        $defaultStart = 0;
        
        if (method_exists($this, 'getDefaultStartValue')) {
            $defaultStart = $this->getDefaultStartValue();
        }

        $this->resetTo($defaultStart);
    }
}
