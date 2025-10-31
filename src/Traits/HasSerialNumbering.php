<?php

namespace AzahariZaman\ControlledNumber\Traits;

use AzahariZaman\ControlledNumber\Models\SerialLog;
use AzahariZaman\ControlledNumber\Services\SerialManager;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSerialNumbering
{
    /**
     * Boot the trait.
     */
    protected static function bootHasSerialNumbering(): void
    {
        static::creating(function ($model) {
            if (method_exists($model, 'shouldGenerateSerial') && !$model->shouldGenerateSerial()) {
                return;
            }

            $model->generateSerialNumber();
        });
    }

    /**
     * Generate a serial number for this model.
     */
    public function generateSerialNumber(?string $patternName = null): string
    {
        $patternName = $patternName ?? $this->getSerialPatternName();
        
        $manager = app(SerialManager::class);
        $serial = $manager->generate($patternName, $this, $this->getSerialContext());

        // Store serial in model if column exists
        if ($this->hasSerialColumn()) {
            $this->setAttribute($this->getSerialColumn(), $serial);
        }

        return $serial;
    }

    /**
     * Preview what the next serial number would be.
     */
    public function previewSerialNumber(?string $patternName = null): string
    {
        $patternName = $patternName ?? $this->getSerialPatternName();
        $manager = app(SerialManager::class);
        
        return $manager->preview($patternName, $this, $this->getSerialContext());
    }

    /**
     * Get all serial logs for this model.
     */
    public function serialLogs(): MorphMany
    {
        return $this->morphMany(SerialLog::class, 'model');
    }

    /**
     * Get the active serial log for this model.
     */
    public function activeSerialLog(): ?SerialLog
    {
        return $this->serialLogs()->active()->latest()->first();
    }

    /**
     * Get the pattern name for serial generation.
     * Override this method in your model to specify the pattern.
     */
    protected function getSerialPatternName(): string
    {
        return property_exists($this, 'serialPattern') 
            ? $this->serialPattern 
            : strtolower(class_basename($this));
    }

    /**
     * Get additional context for serial generation.
     * Override this method to provide custom context.
     */
    protected function getSerialContext(): array
    {
        return property_exists($this, 'serialContext') ? $this->serialContext : [];
    }

    /**
     * Check if the model has a serial column.
     */
    protected function hasSerialColumn(): bool
    {
        $column = $this->getSerialColumn();
        return in_array($column, $this->getFillable()) || 
               (method_exists($this, 'getConnection') && 
                \Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), $column));
    }

    /**
     * Get the serial column name.
     * Override this to use a different column name.
     */
    protected function getSerialColumn(): string
    {
        return property_exists($this, 'serialColumn') ? $this->serialColumn : 'serial_number';
    }

    /**
     * Void the serial number for this model.
     */
    public function voidSerial(?string $reason = null): bool
    {
        $log = $this->activeSerialLog();
        
        if (!$log) {
            return false;
        }

        $log->void($reason);
        return true;
    }

    /**
     * Check if this model should generate a serial automatically.
     * Override this method to add custom logic.
     */
    public function shouldGenerateSerial(): bool
    {
        // Don't generate if serial already exists
        if ($this->hasSerialColumn() && $this->getAttribute($this->getSerialColumn())) {
            return false;
        }

        return true;
    }
}
