<?php

namespace AzahariZaman\ControlledNumber\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use AzahariZaman\ControlledNumber\Events\SerialNumberVoided;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use AzahariZaman\ControlledNumber\Exceptions\SerialDeletionNotAllowedException;
use Illuminate\Support\Carbon;

class SerialLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'serial',
        'pattern_name',
        'model_type',
        'model_id',
        'user_id',
        'generated_at',
        'voided_at',
        'void_reason',
        'is_void',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_void' => 'boolean',
        'generated_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    /**
     * Get the parent model that owns the serial.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who generated the serial.
     */
    public function user(): BelongsTo
    {
        $userModel = 'App\\Models\\User';
        if (function_exists('config')) {
            $userModel = config('auth.providers.users.model', $userModel);
        }
        return $this->belongsTo($userModel);
    }

    /**
     * Get the serial sequence associated with this log.
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(SerialSequence::class, 'pattern_name', 'name');
    }

    /**
     * Mark the serial as void.
     */
    public function void(?string $reason = null): void
    {
        $this->update([
            'is_void' => true,
            'voided_at' => Carbon::now(),
            'void_reason' => $reason,
        ]);

        if (function_exists('event')) {
            event(new SerialNumberVoided($this, $this->serial, $reason));
        }
    }

    /**
     * Prevent deletion of serial logs for audit trail integrity.
     *
     * @throws SerialDeletionNotAllowedException
     */
    public function delete(): never
    {
        throw new SerialDeletionNotAllowedException();
    }

    /**
     * Scope a query to only include active (non-voided) serials.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_void', false);
    }

    /**
     * Scope a query to only include voided serials.
     */
    public function scopeVoided(Builder $query): Builder
    {
        return $query->where('is_void', true);
    }

    /**
     * Scope a query to filter by pattern name.
     */
    public function scopeForPattern(Builder $query, string $patternName): Builder
    {
        return $query->where('pattern_name', $patternName);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('generated_at', [$startDate, $endDate]);
    }
}
