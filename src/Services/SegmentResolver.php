<?php

namespace AzahariZaman\ControlledNumber\Services;

use AzahariZaman\ControlledNumber\Contracts\SegmentInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SegmentResolver
{
    protected array $customResolvers = [];
    protected bool $cacheEnabled = true;
    protected int $cacheTtl = 3600;

    public function __construct()
    {
        $this->customResolvers = function_exists('config') ? config('serial-pattern.segments', []) : [];
    }

    /**
     * Resolve all segments in a pattern.
     */
    public function resolveAll(array $segments, ?Model $model = null, array $context = []): array
    {
        $resolved = [];

        foreach ($segments as $segment) {
            $resolved[$segment] = $this->resolve($segment, $model, $context);
        }

        return $resolved;
    }

    /**
     * Resolve a single segment.
     */
    public function resolve(string $segment, ?Model $model = null, array $context = []): string
    {
        // Check for custom resolver first
        if (isset($this->customResolvers[$segment])) {
            return $this->resolveCustom($segment, $model, $context);
        }

        // Check if it's a model property (e.g., department.code)
        if (strpos($segment, '.') !== false) {
            return $this->resolveModelProperty($segment, $model);
        }

        // Resolve built-in segments
        return match($segment) {
            'number' => $context['number'] ?? '0',
            'year' => Carbon::now()->format('Y'),
            'year_short' => Carbon::now()->format('y'),
            'month' => Carbon::now()->format('m'),
            'month_name' => Carbon::now()->format('M'),
            'day' => Carbon::now()->format('d'),
            'hour' => Carbon::now()->format('H'),
            'minute' => Carbon::now()->format('i'),
            'second' => Carbon::now()->format('s'),
            'week' => Carbon::now()->format('W'),
            'quarter' => (string)Carbon::now()->quarter,
            'timestamp' => (string)Carbon::now()->timestamp,
            default => $segment,
        };
    }

    /**
     * Resolve a custom segment using registered resolver.
     */
    protected function resolveCustom(string $segment, ?Model $model = null, array $context = []): string
    {
        $resolverClass = $this->customResolvers[$segment];
        
        if (!class_exists($resolverClass)) {
            return $segment;
        }

        if (function_exists('app')) {
            $resolver = app($resolverClass);
        } else {
            $resolver = new $resolverClass();
        }

        if ($resolver instanceof SegmentInterface) {
            return $resolver->resolve($model, $context);
        }

        return $segment;
    }

    /**
     * Resolve a model property segment (e.g., department.code, user.name).
     */
    protected function resolveModelProperty(string $segment, ?Model $model = null): string
    {
        if ($model === null) {
            return '';
        }

        // Use caching for model properties
        if ($this->cacheEnabled && class_exists('Illuminate\\Support\\Facades\\Cache')) {
            $cacheKey = "serial_segment:{$segment}:" . get_class($model) . ":{$model->getKey()}";
            
            return Cache::remember($cacheKey, $this->cacheTtl, function () use ($segment, $model) {
                return $this->extractModelProperty($segment, $model);
            });
        }

        return $this->extractModelProperty($segment, $model);
    }

    /**
     * Extract property value from model using dot notation.
     */
    protected function extractModelProperty(string $segment, Model $model): string
    {
        $parts = explode('.', $segment);
        $value = $model;

        foreach ($parts as $part) {
            if ($value instanceof Model) {
                // Handle relationships
                if ($value->relationLoaded($part)) {
                    $value = $value->getRelation($part);
                } elseif (method_exists($value, $part)) {
                    $value = $value->$part;
                } elseif (isset($value->$part)) {
                    $value = $value->$part;
                } else {
                    return '';
                }
            } elseif (is_object($value) && isset($value->$part)) {
                $value = $value->$part;
            } elseif (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return '';
            }
        }

        return (string)$value;
    }

    /**
     * Register a custom segment resolver.
     */
    public function registerResolver(string $segment, string $resolverClass): void
    {
        $this->customResolvers[$segment] = $resolverClass;
    }

    /**
     * Enable or disable caching.
     */
    public function setCaching(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;
    }

    /**
     * Set cache TTL in seconds.
     */
    public function setCacheTtl(int $seconds): void
    {
        $this->cacheTtl = $seconds;
    }
}
