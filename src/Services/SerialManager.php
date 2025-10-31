<?php

namespace AzahariZaman\ControlledNumber\Services;

use AzahariZaman\ControlledNumber\Enums\ResetType;
use AzahariZaman\ControlledNumber\Events\SerialNumberGenerated;
use AzahariZaman\ControlledNumber\Exceptions\InvalidPatternException;
use AzahariZaman\ControlledNumber\Exceptions\SerialCollisionException;
use AzahariZaman\ControlledNumber\Models\SerialLog;
use AzahariZaman\ControlledNumber\Models\SerialSequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SerialManager
{
    protected SegmentResolver $resolver;
    protected array $patterns = [];

    public function __construct(SegmentResolver $resolver)
    {
        $this->resolver = $resolver;
        $this->patterns = config('serial-pattern.patterns', []);
    }

    /**
     * Generate a serial number for a given pattern.
     *
     * @throws InvalidPatternException
     * @throws SerialCollisionException
     */
    public function generate(string $patternName, ?Model $model = null, array $context = []): string
    {
        // Get or create pattern configuration
        $patternConfig = $this->getPatternConfig($patternName);
        $pattern = new SerialPattern($patternConfig['pattern'], $patternConfig);
        
        // Validate pattern
        $pattern->validate();

        // Use atomic lock to prevent race conditions
        $lockKey = "serial_generation:{$patternName}";
        $lockTimeout = config('serial-pattern.lock.timeout', 10);

        if (config('serial-pattern.lock.enabled', true)) {
            return Cache::lock($lockKey, $lockTimeout)->block($lockTimeout, function () use ($patternName, $pattern, $model, $context) {
                return $this->generateSerial($patternName, $pattern, $model, $context);
            });
        }

        return $this->generateSerial($patternName, $pattern, $model, $context);
    }

    /**
     * Generate the serial number (internal method).
     */
    protected function generateSerial(string $patternName, SerialPattern $pattern, ?Model $model, array $context): string
    {
        return DB::transaction(function () use ($patternName, $pattern, $model, $context) {
            // Get or create sequence
            $sequence = $this->getOrCreateSequence($patternName, $pattern);

            // Check if reset is needed
            if ($sequence->shouldReset()) {
                $numberConfig = $pattern->getNumberConfig();
                $sequence->reset($numberConfig['start']);
            }

            // Get next number
            $nextNumber = $sequence->incrementNumber();
            
            // Resolve all segments
            $context['number'] = $pattern->formatNumber($nextNumber);
            $resolvedSegments = $this->resolver->resolveAll($pattern->getSegments(), $model, $context);

            // Build serial
            $serial = $pattern->build($resolvedSegments);

            // Check for collisions
            $this->ensureUniqueness($serial, $patternName);

            // Log if enabled
            if (config('serial-pattern.logging.enabled', true)) {
                $this->logSerial($serial, $patternName, $model);
            }

            return $serial;
        });
    }

    /**
     * Get or create a serial sequence.
     */
    protected function getOrCreateSequence(string $patternName, SerialPattern $pattern): SerialSequence
    {
        $sequence = SerialSequence::where('name', $patternName)->first();

        if (!$sequence) {
            $patternConfig = $pattern->getConfig();
            $numberConfig = $pattern->getNumberConfig();

            $sequence = SerialSequence::create([
                'name' => $patternName,
                'pattern' => $pattern->getPattern(),
                'current_number' => $numberConfig['start'] - 1,
                'reset_type' => ResetType::from($patternConfig['reset'] ?? 'never'),
                'reset_interval' => $patternConfig['interval'] ?? null,
                'last_reset_at' => now(),
            ]);
        }

        return $sequence;
    }

    /**
     * Ensure the serial number is unique.
     *
     * @throws SerialCollisionException
     */
    protected function ensureUniqueness(string $serial, string $patternName): void
    {
        if (config('serial-pattern.logging.enabled', true)) {
            $exists = SerialLog::where('serial', $serial)
                ->where('is_void', false)
                ->exists();

            if ($exists) {
                throw new SerialCollisionException($serial, $patternName);
            }
        }
    }

    /**
     * Log the serial number generation.
     */
    protected function logSerial(string $serial, string $patternName, ?Model $model): SerialLog
    {
        $userId = null;
        
        if (config('serial-pattern.logging.track_user', true) && auth()->check()) {
            $userId = auth()->id();
        }

        $log = SerialLog::create([
            'serial' => $serial,
            'pattern_name' => $patternName,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->getKey() : null,
            'user_id' => $userId,
            'generated_at' => now(),
            'is_void' => false,
        ]);

        event(new SerialNumberGenerated($log, $serial, $patternName));

        return $log;
    }

    /**
     * Preview a serial number without generating it.
     */
    public function preview(string $patternName, ?Model $model = null, array $context = []): string
    {
        $patternConfig = $this->getPatternConfig($patternName);
        $pattern = new SerialPattern($patternConfig['pattern'], $patternConfig);
        
        $pattern->validate();

        // Get current or simulated number
        $sequence = SerialSequence::where('name', $patternName)->first();
        $nextNumber = $sequence ? $sequence->current_number + 1 : ($patternConfig['start'] ?? 1);

        // Resolve segments
        $context['number'] = $pattern->formatNumber($nextNumber);
        $resolvedSegments = $this->resolver->resolveAll($pattern->getSegments(), $model, $context);

        return $pattern->build($resolvedSegments);
    }

    /**
     * Void a serial number.
     */
    public function void(string $serial, ?string $reason = null): bool
    {
        $log = SerialLog::where('serial', $serial)->first();

        if (!$log) {
            return false;
        }

        $log->void($reason);
        return true;
    }

    /**
     * Reset a sequence counter.
     */
    public function resetSequence(string $patternName, ?int $startValue = null): bool
    {
        $sequence = SerialSequence::where('name', $patternName)->first();

        if (!$sequence) {
            return false;
        }

        $start = $startValue ?? $this->getPatternConfig($patternName)['start'] ?? 1;
        $sequence->reset($start);

        return true;
    }

    /**
     * Get pattern configuration.
     *
     * @throws InvalidPatternException
     */
    protected function getPatternConfig(string $patternName): array
    {
        if (!isset($this->patterns[$patternName])) {
            throw new InvalidPatternException($patternName, 'Pattern configuration not found');
        }

        return $this->patterns[$patternName];
    }

    /**
     * Register a new pattern at runtime.
     */
    public function registerPattern(string $name, array $config): void
    {
        $this->patterns[$name] = $config;
    }

    /**
     * Get all registered patterns.
     */
    public function getPatterns(): array
    {
        return $this->patterns;
    }

    /**
     * Check if a pattern exists.
     */
    public function hasPattern(string $patternName): bool
    {
        return isset($this->patterns[$patternName]);
    }
}
