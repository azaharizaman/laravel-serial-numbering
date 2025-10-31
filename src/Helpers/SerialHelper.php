<?php

namespace Azahari\SerialPattern\Helpers;

use Azahari\SerialPattern\Models\SerialLog;
use Azahari\SerialPattern\Services\SerialManager;
use Illuminate\Support\Collection;

class SerialHelper
{
    /**
     * Generate a serial number.
     */
    public static function generate(string $patternName, $model = null, array $context = []): string
    {
        return app(SerialManager::class)->generate($patternName, $model, $context);
    }

    /**
     * Preview a serial number.
     */
    public static function preview(string $patternName, $model = null, array $context = []): string
    {
        return app(SerialManager::class)->preview($patternName, $model, $context);
    }

    /**
     * Void a serial number.
     */
    public static function void(string $serial, ?string $reason = null): bool
    {
        return app(SerialManager::class)->void($serial, $reason);
    }

    /**
     * Check if a serial number exists.
     */
    public static function exists(string $serial): bool
    {
        return SerialLog::where('serial', $serial)->exists();
    }

    /**
     * Check if a serial number is active (not voided).
     */
    public static function isActive(string $serial): bool
    {
        return SerialLog::where('serial', $serial)
            ->where('is_void', false)
            ->exists();
    }

    /**
     * Get serial log by serial number.
     */
    public static function getLog(string $serial): ?SerialLog
    {
        return SerialLog::where('serial', $serial)->first();
    }

    /**
     * Format a number with leading zeros.
     */
    public static function formatNumber(int $number, int $digits = 4): string
    {
        return str_pad((string)$number, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Export serial logs to array.
     */
    public static function exportLogs(array $filters = []): Collection
    {
        $query = SerialLog::query();

        if (isset($filters['pattern'])) {
            $query->forPattern($filters['pattern']);
        }

        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['is_void'])) {
            $query->where('is_void', $filters['is_void']);
        }

        return $query->get();
    }

    /**
     * Export serial logs to CSV format.
     */
    public static function exportToCsv(array $filters = []): string
    {
        $logs = self::exportLogs($filters);
        
        if ($logs->isEmpty()) {
            return '';
        }

        $csv = [];
        $csv[] = ['Serial', 'Pattern', 'Model Type', 'Model ID', 'User ID', 'Generated At', 'Voided At', 'Void Reason', 'Is Void'];

        foreach ($logs as $log) {
            $csv[] = [
                $log->serial,
                $log->pattern_name,
                $log->model_type,
                $log->model_id,
                $log->user_id,
                $log->generated_at?->toDateTimeString(),
                $log->voided_at?->toDateTimeString(),
                $log->void_reason,
                $log->is_void ? 'Yes' : 'No',
            ];
        }

        $output = '';
        foreach ($csv as $row) {
            $output .= implode(',', array_map(function ($field) {
                return '"' . str_replace('"', '""', $field ?? '') . '"';
            }, $row)) . "\n";
        }

        return $output;
    }

    /**
     * Export serial logs to JSON format.
     */
    public static function exportToJson(array $filters = []): string
    {
        $logs = self::exportLogs($filters);
        return $logs->toJson(JSON_PRETTY_PRINT);
    }

    /**
     * Get statistics for a pattern.
     */
    public static function getPatternStats(string $patternName): array
    {
        $total = SerialLog::forPattern($patternName)->count();
        $active = SerialLog::forPattern($patternName)->active()->count();
        $voided = SerialLog::forPattern($patternName)->voided()->count();

        return [
            'pattern' => $patternName,
            'total' => $total,
            'active' => $active,
            'voided' => $voided,
            'void_rate' => $total > 0 ? round(($voided / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Validate a pattern string.
     */
    public static function validatePattern(string $pattern): array
    {
        $errors = [];

        if (empty($pattern)) {
            $errors[] = 'Pattern cannot be empty';
        }

        if (!preg_match('/\{[^}]+\}/', $pattern)) {
            $errors[] = 'Pattern must contain at least one segment (e.g., {year}, {number})';
        }

        if (!str_contains($pattern, '{number}')) {
            $errors[] = 'Pattern must contain {number} segment';
        }

        preg_match_all('/\{([^}]+)\}/', $pattern, $matches);
        foreach ($matches[1] as $segment) {
            if (!preg_match('/^[a-zA-Z0-9_.]+$/', $segment)) {
                $errors[] = "Invalid segment name: {$segment}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
