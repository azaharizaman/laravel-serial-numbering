# Roadmap – Laravel Serial Pattern

This document outlines planned enhancements and future features for the package.

---

## ✅ Version 1.1.0 – Enhanced Audit & Custom Resets *(Released 2025-11-10)*

### Custom Reset Logic
**Priority:** High  
**Status:** ✅ **COMPLETED**

User-defined reset periods beyond standard intervals:

- ✅ **Fiscal Year Resets**: Configurable fiscal calendars with custom start month/day
- ✅ **Business Day Resets**: Automatically skips weekends and holidays
- ✅ **Custom Strategy Interface**: `ResetStrategyInterface` for pluggable reset logic
- ✅ **Custom Segments**: `{fiscal_year}` segment for pattern integration
- ✅ **Database Support**: Migration for `reset_strategy_class` and `reset_strategy_config` columns

**Implementation:**
```php
'patterns' => [
    'fiscal_invoice' => [
        'pattern' => 'FY{fiscal_year}-{number}',
        'reset' => \AzahariZaman\ControlledNumber\Enums\ResetType::CUSTOM,
        'reset_strategy' => \AzahariZaman\ControlledNumber\Resets\FiscalYearReset::class,
        'reset_strategy_config' => [
            'start_month' => 4,
            'start_day' => 1,
        ],
    ],
]
```

**Documentation:** See [CUSTOM_RESET_STRATEGIES.md](CUSTOM_RESET_STRATEGIES.md)

---

### Enhanced Audit Logging with Spatie Activity Log
**Priority:** Medium  
**Status:** ✅ **COMPLETED**

Integrated `spatie/laravel-activitylog` for richer audit capabilities:

- ✅ **Structured Activity Logging**: Serial generation, voids, and resets logged as activities
- ✅ **Multi-Tenant Support**: Automatic tenant_id tracking
- ✅ **Custom Properties**: Stores pattern name, user, model type/id, and context
- ✅ **Activity Timeline**: Query full history across all serial operations
- ✅ **Centralized Trait**: `LogsSerialActivity` trait for consistent logging

**Implementation:**
```php
// Automatic logging on serial generation (via LogsSerialActivity trait)
activity()
    ->performedOn($model)
    ->causedBy($user)
    ->withProperties([
        'serial' => $serial,
        'pattern' => $patternName,
        'tenant_id' => tenant()->id ?? null,
    ])
    ->log('serial_generated');
```

**Dependencies:**
- ✅ `spatie/laravel-activitylog` ^4.8

**Configuration:** See `config/serial-pattern.php` → `activity_log` section

---

### RESTful API Endpoints
**Priority:** Medium  
**Status:** ✅ **COMPLETED**

Serial generation exposed via REST API for external integrations:

**Endpoints:**
- ✅ `POST /api/v1/serial-numbers/generate` – Generate new serial
- ✅ `GET /api/v1/serial-numbers/{type}/peek` – Preview next serial without generation
- ✅ `POST /api/v1/serial-numbers/{type}/reset` – Reset sequence counter
- ✅ `POST /api/v1/serial-numbers/{serial}/void` – Void existing serial
- ✅ `GET /api/v1/serial-numbers/logs` – Query audit logs with filters and pagination

**Features:**
- ✅ Token-based authentication (Laravel Sanctum)
- ✅ Rate limiting per pattern type (configurable)
- ✅ JSON API resources for consistent responses
- ✅ Full API documentation with curl examples

**Example Request:**
```bash
curl -X POST https://api.example.com/api/v1/serial-numbers/generate \
  -H "Authorization: Bearer {token}" \
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

**Documentation:** See [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

---

### High-Concurrency Stress Tests
**Priority:** High  
**Status:** ✅ **COMPLETED**

Comprehensive stress testing for high-concurrency scenarios:

- ✅ **Concurrent Generation Tests**: 10, 50, and 100+ simultaneous generations
- ✅ **Uniqueness Validation**: Ensures no collisions under load
- ✅ **Performance Benchmarks**: Sub-5-second generation for 100 serials
- ✅ **Memory Profiling**: Monitors memory usage during high-volume operations
- ✅ **Reset Stress Tests**: Concurrent reset with race condition detection
- ✅ **Pattern Isolation**: Verifies independent pattern behavior

**Test Suite:** `tests/Stress/ConcurrencyStressTest.php` (7 tests)

**Running Stress Tests:**
```bash
# Run all tests including stress tests
vendor/bin/phpunit --group stress

# Exclude stress tests (default for package environment)
vendor/bin/phpunit --exclude-group stress
```

**Note:** Stress tests require a full Laravel application environment with Cache facade support.

---

## 📋 Version 1.3.0 – API Enhancements *(Planned Q1 2026)*

### API Webhooks & Events
**Priority:** Medium  
**Status:** ⏳ Planned

- Webhook notifications for serial lifecycle events
- Configurable webhook endpoints per pattern
- Event batching and retry logic
- Webhook signature verification

### OpenAPI/Swagger Documentation
**Priority:** Low  
**Status:** ⏳ Planned

- Auto-generated OpenAPI 3.0 specification
- Interactive Swagger UI
- Postman collection export

---

### Concurrency Stress Tests
**Priority:** High  
**Status:** Planned

Add comprehensive stress testing for high-concurrency scenarios:

- **Load Testing**: Simulate 1,000+ simultaneous requests
- **Race Condition Detection**: Verify lock mechanisms under stress
- **Performance Benchmarks**: Measure throughput and latency
- **Database Deadlock Prevention**: Test transaction isolation

**Tools:**
- Laravel Dusk for parallel browser tests
- Apache JMeter for load testing
- Custom PHPUnit concurrency tests with `parallel-lint`

**Test Scenarios:**
```php
// Simulate 1000 concurrent serial generations
test('handles 1000 concurrent requests without collision', function () {
    $processes = [];
    
    for ($i = 0; $i < 1000; $i++) {
        $processes[] = async(fn() => Serial::generate('invoice'));
    }
    
    $serials = await($processes);
    
    expect($serials)
        ->toHaveCount(1000)
        ->toBeUnique();
});
```

**Issues Tracking:** TBD

---

## 📋 Version 2.0.0 – Blockchain Verification

### Blockchain Integration
**Priority:** Low  
**Status:** Research Phase

Add optional blockchain verification for critical serial numbers (invoices, legal documents, certificates):

**Features:**
- **Hash Generation**: Create SHA-256 hashes for serial records
- **Blockchain Storage**: Store hashes on Ethereum, Polygon, or private chains
- **Verification Endpoint**: Validate serial authenticity against blockchain
- **Immutable Proof**: Tamper-proof audit trail for compliance

**Database Schema:**
```php
Schema::create('blockchain_verifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('serial_log_id')->constrained()->cascadeOnDelete();
    $table->string('blockchain_hash', 66); // 0x + 64 hex chars
    $table->string('blockchain_network'); // ethereum, polygon, etc.
    $table->string('transaction_hash')->nullable();
    $table->timestamp('verified_at');
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

**API Endpoint:**
```bash
POST /api/v1/serial-numbers/verify
{
  "serial": "INV-2024-10-01000",
  "blockchain_hash": "0xabc123..."
}
```

**Dependencies:**
- `web3p/web3.php` for Ethereum integration
- Optional: Private blockchain SDK (Hyperledger, Quorum)

**Considerations:**
- Gas fees for public chains
- Privacy concerns (store only hashes, not raw data)
- Compliance with GDPR (right to be forgotten vs. immutable chains)

**Issues Tracking:** TBD

---

## 🎯 Community Requests

Have a feature request? [Open an issue](https://github.com/azaharizaman/laravel-serial-numbering/issues/new) with the label `enhancement`.

### Voting System
Vote on upcoming features by reacting to issues with 👍 (upvote) or 👎 (downvote).

---

## 📅 Release Schedule

- **v1.0.0-rc1**: Initial pre-release (Current)
- **v1.0.0**: Stable release (Q1 2025)
- **v1.1.0**: Custom resets + Spatie logging (Q2 2025)
- **v1.2.0**: API endpoints + Stress tests (Q3 2025)
- **v2.0.0**: Blockchain integration (Q4 2025+)

---

## 🤝 Contributing

Interested in implementing any of these features? Check our [CONTRIBUTING.md](CONTRIBUTING.md) guide and submit a PR!

### Priority Labels
- 🔴 **High Priority**: Core functionality improvements
- 🟡 **Medium Priority**: Nice-to-have enhancements
- 🟢 **Low Priority**: Experimental/research features

---

**Last Updated:** November 10, 2025
