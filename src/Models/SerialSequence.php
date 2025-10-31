<?php

namespace Azahari\SerialPattern\Models;

use Azahari\SerialPattern\Enums\ResetType;
use Illuminate\Database\Eloquent\Model;
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
            'last_reset_at' => now(),
        ]);
    }

    /**
     * Check if sequence should be reset based on reset type.
     */
    public function shouldReset(): bool
    {
        return $this->reset_type->shouldReset(
            $this->last_reset_at,
            $this->reset_interval ?? 1
        );
    }
}
