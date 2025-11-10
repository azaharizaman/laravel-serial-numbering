# Laravel Serial Pattern

[![Latest Version on Packagist](https://img.shields.io/packagist/v/azaharizaman/controlled-number.svg?style=flat-square)](https://packagist.org/packages/azaharizaman/controlled-number)
[![Total Downloads](https://img.shields.io/packagist/dt/azaharizaman/controlled-number.svg?style=flat-square)](https://packagist.org/packages/azaharizaman/controlled-number)
[![License](https://img.shields.io/packagist/l/azaharizaman/controlled-number.svg?style=flat-square)](https://packagist.org/packages/azaharizaman/controlled-number)

A powerful Laravel 12 package for generating configurable serial numbers with dynamic segments, custom reset strategies, comprehensive audit logging, and RESTful API support. Perfect for invoices, orders, tickets, and any entity requiring unique sequential identifiers.

## ✨ Features

### Core Features
- 🎯 **Pattern-Based Generation** - Create serial numbers using dynamic segments like `{year}`, `{month}`, `{number}`, and custom model properties
- 🔄 **Auto-Reset Rules** - Configure daily, weekly, monthly, yearly, interval, or custom reset strategies
- 🔒 **Concurrency Safe** - Built-in atomic locks prevent race conditions in high-traffic environments
- 📊 **Audit Logging** - Track every serial number generation with user information and timestamps
- ✅ **Uniqueness Enforcement** - Automatic collision detection and prevention
- 🗑️ **Serial Voiding** - Soft-delete approach for cancelled or erroneous serials
- 🧩 **Eloquent Integration** - Simple trait for seamless model integration
- 🔌 **Extensible** - Register custom segment resolvers for specialized patterns
- 🧪 **Well Tested** - 71 tests with 163 assertions

### New in v1.1.0
- 🔄 **Custom Reset Strategies** - Fiscal year resets, business day resets, and pluggable custom logic
- � **Spatie Activity Log Integration** - Rich audit trail with tenant support and activity timeline
- 🌐 **RESTful API Endpoints** - Generate, preview, reset, void, and query serials via REST API
- 🧪 **Concurrency Stress Tests** - Validated under 100+ concurrent operations

## 📋 Documentation

- **[Quick Start](#quick-start)** - Get started in minutes
- **[API Documentation](API_DOCUMENTATION.md)** - Complete REST API reference
- **[Custom Reset Strategies](CUSTOM_RESET_STRATEGIES.md)** - Build custom reset logic
- **[Examples](EXAMPLES.md)** - Real-world usage examples
- **[Roadmap](ROADMAP.md)** - Upcoming features
- **[Changelog](CHANGELOG.md)** - Version history
- **[Release Notes](RELEASE_NOTES_v1.1.0.md)** - v1.1.0 features

## Installation

Install via Composer:

```bash
composer require azaharizaman/controlled-number
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=serial-pattern-config
```

Run migrations:

```bash
php artisan migrate
```

## Quick Start

### 1. Configure Patterns

Edit `config/serial-pattern.php`:

```php
'patterns' => [
    'invoice' => [
        'pattern' => 'INV-{year}-{month}-{number}',
        'start' => 1000,
        'digits' => 5,
        'reset' => 'monthly',
        'interval' => 1,
    ],
    'order' => [
        'pattern' => 'ORD-{year}{month}{day}-{number}',
        'start' => 1,
        'digits' => 4,
        'reset' => 'daily',
    ],
],
```

### 2. Use in Models

Add the trait to your Eloquent model:

```php
use AzahariZaman\ControlledNumber\Traits\HasSerialNumbering;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasSerialNumbering;

    protected $serialPattern = 'invoice';
    protected $serialColumn = 'invoice_number';
    
    protected $fillable = ['invoice_number', 'amount', 'customer_id'];
}
```

### 3. Generate Serials

Serials are generated automatically on model creation:

```php
$invoice = Invoice::create([
    'amount' => 1500.00,
    'customer_id' => 1,
]);

echo $invoice->invoice_number; // INV-2024-10-01000
```

Or generate manually:

```php
use AzahariZaman\ControlledNumber\Services\SerialManager;

$manager = app(SerialManager::class);
$serial = $manager->generate('invoice');
```

## Available Segments

### Built-in Date/Time Segments

- `{year}` - Four-digit year (e.g., 2024)
- `{year_short}` - Two-digit year (e.g., 24)
- `{month}` - Two-digit month (01-12)
- `{month_name}` - Short month name (Jan-Dec)
- `{day}` - Two-digit day (01-31)
- `{hour}` - Two-digit hour (00-23)
- `{minute}` - Two-digit minute (00-59)
- `{second}` - Two-digit second (00-59)
- `{week}` - ISO week number (01-53)
- `{quarter}` - Quarter number (1-4)
- `{timestamp}` - Unix timestamp

### Model Property Segments

Use dot notation to access model properties and relationships:

```php
'pattern' => 'INV-{year}-{department.code}-{number}'
```

### Custom Segments

Register custom segment resolvers in `config/serial-pattern.php`:

```php
'segments' => [
    'custom.branch' => \App\Segments\BranchCodeResolver::class,
],
```

Create your resolver:

```php
namespace App\Segments;

use AzahariZaman\ControlledNumber\Contracts\SegmentInterface;
use Illuminate\Database\Eloquent\Model;

class BranchCodeResolver implements SegmentInterface
{
    public function resolve(?Model $model = null, array $context = []): string
    {
        return auth()->user()->branch->code ?? 'HQ';
    }

    public function getName(): string
    {
        return 'custom.branch';
    }

    public function validate(): bool
    {
        return true;
    }
}
```

## Reset Types

Configure automatic counter resets:

- `never` - Counter never resets
- `daily` - Reset at midnight each day
- `weekly` - Reset at start of each week
- `monthly` - Reset on first day of each month
- `yearly` - Reset on January 1st
- `interval` - Reset after specified number of days

```php
'invoice' => [
    'pattern' => 'INV-{year}{month}-{number}',
    'reset' => 'monthly',
    'interval' => 1, // Required for 'interval' reset type
],
```

## Advanced Usage

### Preview Serial Numbers

Preview the next serial without generating it:

```php
$manager = app(SerialManager::class);
$preview = $manager->preview('invoice');
```

Or in a model:

```php
$nextSerial = $invoice->previewSerialNumber();
```

### Void Serial Numbers

Mark a serial as void (soft delete for audit purposes):

```php
$manager->void('INV-2024-10-01000', 'Duplicate invoice');

// Or via model
$invoice->voidSerial('Customer cancelled order');
```

### Manual Reset

Reset a sequence counter manually:

```php
$manager->resetSequence('invoice'); // Reset to configured start value
$manager->resetSequence('invoice', 5000); // Reset to specific value
```

### Export Audit Logs

```php
use AzahariZaman\ControlledNumber\Helpers\SerialHelper;

// Export to CSV
$csv = SerialHelper::exportToCsv([
    'pattern' => 'invoice',
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
]);

// Export to JSON
$json = SerialHelper::exportToJson(['is_void' => false]);
```

### Pattern Statistics

```php
$stats = SerialHelper::getPatternStats('invoice');
/*
[
    'pattern' => 'invoice',
    'total' => 1523,
    'active' => 1487,
    'voided' => 36,
    'void_rate' => 2.36,
]
*/
```

## Artisan Commands

### Validate Patterns

Check all configured patterns for errors:

```bash
php artisan serial:validate-patterns
```

Validate a specific pattern:

```bash
php artisan serial:validate-patterns --pattern=invoice
```

Show statistics:

```bash
php artisan serial:validate-patterns --stats
```

## Query Scopes

The `SerialLog` model provides useful query scopes:

```php
use AzahariZaman\ControlledNumber\Models\SerialLog;

// Get active serials
$active = SerialLog::active()->get();

// Get voided serials
$voided = SerialLog::voided()->get();

// Filter by pattern
$invoices = SerialLog::forPattern('invoice')->get();

// Filter by user
$userSerials = SerialLog::byUser(auth()->id())->get();

// Filter by date range
$recent = SerialLog::betweenDates('2024-10-01', '2024-10-31')->get();
```

## Configuration

### Disable Logging

```php
'logging' => [
    'enabled' => false,
    'track_user' => false,
],
```

### Concurrency Settings

```php
'lock' => [
    'enabled' => true,
    'timeout' => 10, // seconds
    'store' => 'redis', // cache store for locks
],
```

## Testing

Run the test suite:

```bash
composer test
```

## Security

Serial logs cannot be deleted for audit trail integrity. Attempting to delete will throw `SerialDeletionNotAllowedException`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on recent changes.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Azahari Zaman](https://github.com/azahari)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Support

- **Documentation**: [Full documentation](https://github.com/azaharizaman/controlled-number)
- **Issues**: [GitHub Issues](https://github.com/azaharizaman/controlled-number/issues)
- **Email**: azaharizaman@gmail.com
