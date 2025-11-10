# Custom Reset Strategies

This guide explains how to implement and use custom reset strategies for serial number sequences.

## Overview

Custom reset strategies allow you to define complex rules for when a sequence counter should reset beyond the built-in options (daily, monthly, yearly, etc.).

## Built-in Custom Strategies

### 1. Fiscal Year Reset

Resets the sequence at the start of your organization's fiscal year.

#### Configuration

```php
use AzahariZaman\ControlledNumber\Resets\FiscalYearReset;

'patterns' => [
    'fiscal_invoice' => [
        'pattern' => 'FY{fiscal_year}-{number}',
        'start' => 1,
        'digits' => 5,
        'reset' => \AzahariZaman\ControlledNumber\Enums\ResetType::CUSTOM,
        'reset_strategy' => FiscalYearReset::class,
        'reset_strategy_config' => [
            'start_month' => 4,  // April
            'start_day' => 1,    // 1st
        ],
    ],
],
```

#### Segments

The `FiscalYearReset` strategy provides a `{fiscal_year}` segment:

```php
// If fiscal year starts April 1, 2024:
// Generated on March 15, 2025 → FY2024-00001
// Generated on April 5, 2025  → FY2025-00001
```

#### API Usage

```php
use AzahariZaman\ControlledNumber\Services\SerialManager;

$manager = app(SerialManager::class);
$serial = $manager->generate('fiscal_invoice');
```

#### Features

- Configurable fiscal year start date
- Automatic year boundary detection
- ISO 8601 fiscal year code support
- Handles leap years correctly

---

### 2. Business Day Reset

Resets the sequence on the next business day (skips weekends and holidays).

#### Configuration

```php
use AzahariZaman\ControlledNumber\Resets\BusinessDayReset;

'patterns' => [
    'daily_report' => [
        'pattern' => 'RPT-{date:Ymd}-{number}',
        'start' => 1,
        'digits' => 4,
        'reset' => \AzahariZaman\ControlledNumber\Enums\ResetType::CUSTOM,
        'reset_strategy' => BusinessDayReset::class,
        'reset_strategy_config' => [
            'holidays' => [
                '2025-01-01', // New Year's Day
                '2025-12-25', // Christmas
            ],
        ],
    ],
],
```

#### Features

- Skips weekends (Saturday and Sunday)
- Configurable holiday list
- Automatic business day calculation
- Handles multi-day gaps (e.g., long weekends)

#### Example Behavior

```php
// Friday March 7, 2025    → RPT-20250307-0001
// Saturday March 8, 2025  → RPT-20250307-0002 (no reset, weekend)
// Monday March 10, 2025   → RPT-20250310-0001 (reset on business day)
```

---

## Creating Custom Reset Strategies

### Step 1: Implement the Interface

Create a class that implements `ResetStrategyInterface`:

```php
<?php

namespace App\Resets;

use AzahariZaman\ControlledNumber\Contracts\ResetStrategyInterface;
use AzahariZaman\ControlledNumber\Models\SerialSequence;
use Carbon\Carbon;

class QuarterlyReset implements ResetStrategyInterface
{
    public function __construct(
        protected array $config = []
    ) {}

    public function shouldReset(SerialSequence $sequence): bool
    {
        if (!$sequence->last_reset_at) {
            return false;
        }

        $lastResetQuarter = $sequence->last_reset_at->quarter;
        $currentQuarter = now()->quarter;

        return $lastResetQuarter !== $currentQuarter;
    }

    public function description(): string
    {
        return 'Resets at the start of each fiscal quarter';
    }
}
```

### Step 2: Configure Your Pattern

Add the custom strategy to your pattern configuration:

```php
'patterns' => [
    'quarterly_report' => [
        'pattern' => 'Q{quarter}-{year}-{number}',
        'start' => 1000,
        'digits' => 5,
        'reset' => \AzahariZaman\ControlledNumber\Enums\ResetType::CUSTOM,
        'reset_strategy' => \App\Resets\QuarterlyReset::class,
        'reset_strategy_config' => [
            // Optional config passed to constructor
        ],
    ],
],
```

### Step 3: Register Custom Segments (Optional)

If your strategy needs custom segments:

```php
use AzahariZaman\ControlledNumber\Services\SegmentResolver;

class QuarterlyReset implements ResetStrategyInterface
{
    // ... existing methods ...

    public function registerSegments(SegmentResolver $resolver): void
    {
        $resolver->register('quarter', function() {
            return 'Q' . now()->quarter;
        });
    }
}
```

Then update your service provider:

```php
use AzahariZaman\ControlledNumber\Services\SerialManager;

public function boot()
{
    $this->app->resolving(SerialManager::class, function ($manager) {
        $pattern = config('serial-pattern.patterns.quarterly_report');
        if ($pattern && isset($pattern['reset_strategy'])) {
            $strategy = app($pattern['reset_strategy'], [
                'config' => $pattern['reset_strategy_config'] ?? []
            ]);
            
            if (method_exists($strategy, 'registerSegments')) {
                $strategy->registerSegments($manager->getResolver());
            }
        }
    });
}
```

---

## Advanced Examples

### Calendar Week Reset

```php
class CalendarWeekReset implements ResetStrategyInterface
{
    public function shouldReset(SerialSequence $sequence): bool
    {
        if (!$sequence->last_reset_at) {
            return false;
        }

        return $sequence->last_reset_at->weekOfYear !== now()->weekOfYear
            || $sequence->last_reset_at->year !== now()->year;
    }

    public function description(): string
    {
        return 'Resets at the start of each ISO 8601 calendar week';
    }
}
```

### Time-Based Reset (Every N Hours)

```php
class HourlyIntervalReset implements ResetStrategyInterface
{
    public function __construct(
        protected array $config = ['hours' => 24]
    ) {}

    public function shouldReset(SerialSequence $sequence): bool
    {
        if (!$sequence->last_reset_at) {
            return false;
        }

        $hours = $this->config['hours'] ?? 24;
        $nextReset = $sequence->last_reset_at->addHours($hours);

        return now()->gte($nextReset);
    }

    public function description(): string
    {
        $hours = $this->config['hours'] ?? 24;
        return "Resets every {$hours} hours";
    }
}
```

### Conditional Reset (Based on External Data)

```php
class ProjectPhaseReset implements ResetStrategyInterface
{
    public function shouldReset(SerialSequence $sequence): bool
    {
        if (!$sequence->last_reset_at) {
            return false;
        }

        // Example: Reset when project phase changes
        $currentPhase = cache()->get('current_project_phase');
        $lastPhase = cache()->get('last_phase_at_reset');

        return $currentPhase !== $lastPhase;
    }

    public function description(): string
    {
        return 'Resets when project phase changes';
    }
}
```

---

## Testing Custom Strategies

### Unit Test Example

```php
use Tests\TestCase;
use App\Resets\QuarterlyReset;
use AzahariZaman\ControlledNumber\Models\SerialSequence;
use Carbon\Carbon;

class QuarterlyResetTest extends TestCase
{
    public function test_it_resets_at_quarter_boundary()
    {
        $strategy = new QuarterlyReset();
        
        $sequence = new SerialSequence([
            'last_reset_at' => Carbon::parse('2025-03-31'), // Q1
        ]);

        Carbon::setTestNow('2025-04-01'); // Q2
        
        $this->assertTrue($strategy->shouldReset($sequence));
    }

    public function test_it_does_not_reset_within_same_quarter()
    {
        $strategy = new QuarterlyReset();
        
        $sequence = new SerialSequence([
            'last_reset_at' => Carbon::parse('2025-01-15'), // Q1
        ]);

        Carbon::setTestNow('2025-02-20'); // Still Q1
        
        $this->assertFalse($strategy->shouldReset($sequence));
    }
}
```

---

## Configuration Reference

### ResetType Enum

```php
use AzahariZaman\ControlledNumber\Enums\ResetType;

ResetType::NEVER    // Never reset
ResetType::DAILY    // Reset daily at midnight
ResetType::WEEKLY   // Reset weekly on Monday
ResetType::MONTHLY  // Reset monthly on 1st
ResetType::YEARLY   // Reset yearly on Jan 1st
ResetType::INTERVAL // Reset every N units
ResetType::CUSTOM   // Use custom strategy class
```

### Config Structure

```php
'patterns' => [
    'pattern_name' => [
        // Standard configuration
        'pattern' => '{prefix}-{number}',
        'start' => 1,
        'digits' => 5,
        
        // Reset configuration
        'reset' => ResetType::CUSTOM,
        
        // Custom strategy (required when reset = CUSTOM)
        'reset_strategy' => \App\Resets\CustomStrategy::class,
        
        // Custom strategy config (optional, passed to constructor)
        'reset_strategy_config' => [
            'key' => 'value',
        ],
    ],
],
```

---

## Performance Considerations

1. **Caching**: Cache expensive calculations (e.g., holiday lookups)
2. **Database Queries**: Avoid N+1 queries in `shouldReset()`
3. **Complexity**: Keep reset logic simple and fast
4. **Testing**: Always test edge cases (leap years, daylight saving, etc.)

## Best Practices

1. **Immutability**: Don't modify the `SerialSequence` model in `shouldReset()`
2. **Idempotency**: `shouldReset()` should return the same result for the same inputs
3. **Documentation**: Provide clear descriptions via `description()`
4. **Validation**: Validate `$config` in constructor
5. **Time Zones**: Use UTC or be explicit about time zones

---

## Troubleshooting

### Strategy Not Applied

**Problem**: Custom strategy class not being used

**Solution**: Verify:
- Class implements `ResetStrategyInterface`
- Fully qualified class name in config
- Class is autoloadable
- `reset` is set to `ResetType::CUSTOM`

### Reset Not Triggering

**Problem**: Sequence doesn't reset when expected

**Solution**:
- Add logging to `shouldReset()` method
- Check `last_reset_at` value in database
- Verify time zone consistency
- Test with `Carbon::setTestNow()` in unit tests

### Performance Issues

**Problem**: Slow serial generation

**Solution**:
- Profile `shouldReset()` execution time
- Move expensive operations to constructor
- Cache external data lookups
- Consider database indexing

---

## Migration from Built-in Reset Types

Converting from `ResetType::MONTHLY` to `FiscalYearReset`:

**Before**:
```php
'reset' => ResetType::MONTHLY,
```

**After**:
```php
'reset' => ResetType::CUSTOM,
'reset_strategy' => \AzahariZaman\ControlledNumber\Resets\FiscalYearReset::class,
'reset_strategy_config' => [
    'start_month' => 4,
    'start_day' => 1,
],
```

**Migration Command** (optional):
```bash
php artisan serial:migrate-reset-type invoice --from=monthly --to=fiscal
```

---

## Support

For help with custom reset strategies:
- GitHub Issues: https://github.com/azaharizaman/laravel-serial-numbering/issues
- Examples: See `tests/Feature/CustomResetStrategyTest.php`
- Discussions: https://github.com/azaharizaman/laravel-serial-numbering/discussions
