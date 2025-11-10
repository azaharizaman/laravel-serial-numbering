<?php

namespace AzahariZaman\ControlledNumber\Models;

use Illuminate\Database\Eloquent\Model;
use AzahariZaman\ControlledNumber\Enums\ResetType;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SerialSequence extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'pattern',
        'current_number',
        'reset_type',
        'reset_interval',
        'last_reset_at',
        'reset_strategy_class',
        'reset_strategy_config',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_number' => 'integer',
        'reset_interval' => 'integer',
        'last_reset_at' => 'datetime',
        'reset_type' => ResetType::class,
        'reset_strategy_config' => 'array',
    ];

    /**
     * Get all serial logs for this sequence.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(SerialLog::class, 'pattern_name', 'name');
    }

    /**
     * Increment the current number and return the new value.
     */
    public function incrementNumber(): int
    {
        $this->increment('current_number');
        return $this->current_number;
    }

    /**
     * Reset the sequence counter.
     */
    public function reset(int $startValue = 0): void
    {
        $this->update([
            'current_number' => $startValue,
            'last_reset_at' => function_exists('now') ? now() : \Illuminate\Support\Carbon::now(),
        ]);
    }

    /**
     * Check if sequence should be reset based on reset type.
     */
    public function shouldReset(): bool
    {
        // If using custom reset strategy, delegate to that class
        if ($this->reset_type === ResetType::CUSTOM && $this->reset_strategy_class) {
            return $this->evaluateCustomResetStrategy();
        }

        return $this->reset_type->shouldReset(
            $this->last_reset_at,
            $this->reset_interval ?? 1
        );
    }

    /**
     * Evaluate custom reset strategy.
     */
    protected function evaluateCustomResetStrategy(): bool
    {
        $strategyClass = $this->reset_strategy_class;

        if (!class_exists($strategyClass)) {
            return false;
        }

        $config = $this->reset_strategy_config ?? [];
        
        // Instantiate strategy - use positional arguments based on array values
        // The config should provide parameters in the correct order
        $strategy = new $strategyClass(...array_values($config));

        if (!$strategy instanceof \AzahariZaman\ControlledNumber\Contracts\ResetStrategyInterface) {
            return false;
        }

        return $strategy->shouldReset(
            $this->last_reset_at,
            $this->reset_interval ?? 1
        );
    }

    /**
     * Get the custom reset strategy instance.
     */
    public function getResetStrategy(): ?\AzahariZaman\ControlledNumber\Contracts\ResetStrategyInterface
    {
        if ($this->reset_type !== ResetType::CUSTOM || !$this->reset_strategy_class) {
            return null;
        }

        $strategyClass = $this->reset_strategy_class;

        if (!class_exists($strategyClass)) {
            return null;
        }

        $config = $this->reset_strategy_config ?? [];
        
        return new $strategyClass(...array_values($config));
    }
}
