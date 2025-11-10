# Release Notes – Version 1.1.0

**Release Date:** November 10, 2025  
**Package:** `azaharizaman/controlled-number`  
**Version:** 1.1.0  
**License:** MIT

---

## 🎉 What's New in v1.1.0

This release introduces **four major feature enhancements** that significantly expand the package's capabilities for enterprise-grade serial number management. We've added custom reset strategies, enhanced audit logging, RESTful API endpoints, and comprehensive concurrency stress testing.

---

## ✨ Major Features

### 1. 🔄 Custom Reset Strategies

Take full control of when your serial number sequences reset with pluggable custom reset logic.

**What's Included:**
- **ResetStrategyInterface**: Contract for implementing custom reset logic
- **FiscalYearReset**: Built-in support for fiscal year calendars with configurable start dates
- **BusinessDayReset**: Automatically skips weekends and holidays in reset calculations
- **Custom Segments**: New `{fiscal_year}` segment for pattern integration
- **Database Support**: Extensible schema for storing strategy configuration

**Example Usage:**
```php
// config/serial-pattern.php
'patterns' => [
    'fiscal_invoice' => [
        'pattern' => 'FY{fiscal_year}-{number}',
        'start' => 1,
        'digits' => 5,
        'reset' => \AzahariZaman\ControlledNumber\Enums\ResetType::CUSTOM,
        'reset_strategy' => \AzahariZaman\ControlledNumber\Resets\FiscalYearReset::class,
        'reset_strategy_config' => [
            'start_month' => 4,  // April
            'start_day' => 1,
        ],
    ],
],
```

**Use Cases:**
- Government agencies with fiscal year reporting
- Businesses with non-calendar year accounting periods
- Daily operations that need to skip weekends/holidays
- Custom business rules (e.g., reset per project phase)

**Documentation:** [CUSTOM_RESET_STRATEGIES.md](CUSTOM_RESET_STRATEGIES.md)

---

### 2. 📊 Enhanced Audit Logging with Spatie Activity Log

Comprehensive activity tracking with enterprise-ready audit capabilities.

**What's Included:**
- **Spatie Integration**: Full integration with `spatie/laravel-activitylog` package
- **LogsSerialActivity Trait**: Centralized logging with consistent structure
- **Automatic Tracking**: Serial generation, voiding, and resets logged automatically
- **Multi-Tenant Support**: Automatic tenant_id tracking for SaaS applications
- **Rich Context**: Stores pattern name, user, model associations, and custom properties
- **Activity Timeline**: Query full history across all serial operations

**Example:**
```php
// Automatic logging when generating serials
$serial = $serialManager->generate('invoice', $invoice);

// Logs activity with:
// - Event: "serial_generated"
// - Subject: Invoice model instance
// - Causer: Current authenticated user
// - Properties: serial number, pattern name, tenant_id
```

**Query Activity:**
```php
// Get all serial generation activities
$activities = Activity::where('description', 'serial_generated')
    ->where('properties->pattern', 'invoice')
    ->get();

// Get voided serials with reasons
$voidedActivities = Activity::where('description', 'serial_voided')
    ->with('subject', 'causer')
    ->get();
```

**Configuration:**
```php
// config/serial-pattern.php
'activity_log' => [
    'enabled' => env('SERIAL_ACTIVITY_LOG_ENABLED', true),
    'log_serial_generated' => true,
    'log_serial_voided' => true,
    'log_sequence_reset' => true,
    'track_tenant' => true,
],
```

---

### 3. 🌐 RESTful API Endpoints

Expose serial number generation via REST API for external integrations and microservices.

**What's Included:**
- **5 RESTful Endpoints**: Generate, preview, reset, void, and query logs
- **Laravel Sanctum Authentication**: Token-based API security
- **Rate Limiting**: Per-pattern, per-user rate limits to prevent abuse
- **JSON API Resources**: Consistent response formatting
- **Comprehensive Documentation**: Full API reference with curl examples

**Endpoints:**

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/serial-numbers/generate` | Generate a new serial number |
| `GET` | `/api/v1/serial-numbers/{type}/peek` | Preview next serial without generating |
| `POST` | `/api/v1/serial-numbers/{type}/reset` | Reset sequence counter |
| `POST` | `/api/v1/serial-numbers/{serial}/void` | Void an existing serial |
| `GET` | `/api/v1/serial-numbers/logs` | Query audit logs with filters |

**Example Request:**
```bash
curl -X POST https://api.example.com/api/v1/serial-numbers/generate \
  -H "Authorization: Bearer your-api-token" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "invoice",
    "model_type": "App\\Models\\Invoice",
    "model_id": 123,
    "context": {
      "department_id": 5
    }
  }'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "serial": "INV-2025-11-01234",
    "log": {
      "id": 1,
      "serial": "INV-2025-11-01234",
      "pattern_name": "invoice",
      "generated_at": "2025-11-10T12:34:56Z",
      "is_void": false
    }
  },
  "message": "Serial number generated successfully"
}
```

**Configuration:**
```php
// config/serial-pattern.php
'api' => [
    'enabled' => env('SERIAL_API_ENABLED', true),
    'prefix' => 'api/v1/serial-numbers',
    'middleware' => ['api', 'auth:sanctum'],
    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_minutes' => 1,
    ],
],
```

**Documentation:** [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

---

### 4. 🧪 Concurrency Stress Testing

Comprehensive stress test suite to validate performance under high-concurrency scenarios.

**What's Included:**
- **7 Comprehensive Tests**: Cover concurrent generation, uniqueness, performance, and memory usage
- **Uniqueness Validation**: Ensures no collisions under 100+ concurrent generations
- **Performance Benchmarks**: Validates sub-5-second generation for 100 serials
- **Memory Profiling**: Monitors memory usage during high-volume operations
- **Reset Stress Tests**: Tests concurrent resets with race condition detection
- **Pattern Isolation**: Verifies different patterns generate independently

**Test Coverage:**
- `test_generates_unique_serials_under_high_concurrency()` – 100 concurrent generations
- `test_concurrent_generation_with_moderate_load()` – 50 concurrent generations
- `test_concurrent_generation_with_low_load()` – 10 concurrent generations
- `test_high_volume_serial_generation_performance()` – 100 serials in < 5 seconds
- `test_memory_usage_during_high_volume_generation()` – Memory profiling
- `test_concurrent_reset_and_generation()` – Race condition testing
- `test_multiple_patterns_generate_independently()` – Pattern isolation

**Running Tests:**
```bash
# Run all tests excluding stress tests (default)
vendor/bin/phpunit --exclude-group stress

# Run only stress tests (requires Laravel environment)
vendor/bin/phpunit --group stress
```

**Note:** Stress tests require a full Laravel application environment with Cache facade support. They are automatically skipped in standalone package testing.

---

## 📦 Installation & Upgrade

### New Installation

```bash
composer require azaharizaman/controlled-number
```

### Upgrading from v1.0.0

```bash
composer update azaharizaman/controlled-number
```

**Run Migrations:**
```bash
php artisan migrate
```

**Publish Config (Optional):**
```bash
php artisan vendor:publish --tag=serial-pattern-config
```

---

## 🔧 Breaking Changes

**None** – This release is fully backward compatible with v1.0.0. All existing configurations and code will continue to work without modification.

---

## 🗂️ New Configuration Options

### Custom Reset Strategies
```php
'patterns' => [
    'your_pattern' => [
        'reset' => \AzahariZaman\ControlledNumber\Enums\ResetType::CUSTOM,
        'reset_strategy' => \Your\Custom\ResetStrategy::class,
        'reset_strategy_config' => [
            // Custom configuration
        ],
    ],
],
```

### Activity Logging
```php
'activity_log' => [
    'enabled' => true,
    'log_serial_generated' => true,
    'log_serial_voided' => true,
    'log_sequence_reset' => true,
    'track_tenant' => true,
],
```

### API Configuration
```php
'api' => [
    'enabled' => true,
    'prefix' => 'api/v1/serial-numbers',
    'middleware' => ['api', 'auth:sanctum'],
    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_minutes' => 1,
    ],
],
```

---

## 📊 Statistics

- **Total Tests:** 71 (up from 56 in v1.0.0)
- **Assertions:** 163 (up from 139 in v1.0.0)
- **New Files:** 21 new files added
- **New Dependencies:** 4 packages added
- **Lines of Code:** ~2,500 lines of production code
- **Documentation:** 4 comprehensive guides (API, Custom Resets, Examples, README updates)

---

## 🛠️ New Dependencies

```json
{
  "spatie/laravel-activitylog": "^4.8",
  "laravel/sanctum": "^4.0",
  "illuminate/routing": "^11.0|^12.0",
  "illuminate/http": "^11.0|^12.0"
}
```

---

## 📚 Documentation

### New Documentation Files
- **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)**: Complete REST API reference
- **[CUSTOM_RESET_STRATEGIES.md](CUSTOM_RESET_STRATEGIES.md)**: Guide for custom reset strategies

### Updated Documentation
- **[README.md](README.md)**: Updated with new features and examples
- **[EXAMPLES.md](EXAMPLES.md)**: Added API and custom reset examples
- **[CHANGELOG.md](CHANGELOG.md)**: Full v1.1.0 release notes
- **[ROADMAP.md](ROADMAP.md)**: Updated with completed features

---

## 🐛 Bug Fixes

- Fixed Cache facade initialization issues in package tests
- Improved `now()` helper compatibility for standalone package testing
- Enhanced TestConfig loading via composer autoload
- Fixed lint errors for Laravel classes in package environment

---

## ⚠️ Known Limitations

1. **Stress Tests**: Require full Laravel application environment (automatically skipped in package testing)
2. **Activity Log**: Requires `spatie/laravel-activitylog` package installation
3. **API Endpoints**: Require `laravel/sanctum` for authentication

---

## 🔮 What's Next (v1.3.0 – Q1 2026)

- **Webhook Support**: Real-time notifications for serial events
- **OpenAPI/Swagger Spec**: Auto-generated API documentation
- **Postman Collections**: Ready-to-use API collections
- **Performance Optimizations**: Further improvements for high-volume scenarios

See [ROADMAP.md](ROADMAP.md) for detailed future planning.

---

## 🙏 Acknowledgments

Thank you to the Laravel community and all contributors who provided feedback and suggestions during the development of this release.

---

## 📞 Support

- **GitHub Issues**: https://github.com/azaharizaman/laravel-serial-numbering/issues
- **Documentation**: https://github.com/azaharizaman/laravel-serial-numbering
- **Email**: azaharizaman@gmail.com

---

## 📄 License

MIT License – See [LICENSE](LICENSE) file for details.

---

**Happy Serial Numbering! 🎉**
