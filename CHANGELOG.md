# Changelog

All notable changes to `laravel-serial-pattern` will be documented in this file.

## [Unreleased]

### Planned for v2.0.0
- Blockchain verification for critical serials (pending `azaharizaman/pp-blockchain` package)
- Advanced compliance features
- Multi-tenant isolation improvements

See [ROADMAP.md](ROADMAP.md) for detailed feature planning.

---

## [1.1.0] - 2025-11-10

### Added

#### 🔄 Custom Reset Strategies
- **Custom Reset Strategy Interface**: `ResetStrategyInterface` for pluggable reset logic
- **Fiscal Year Reset**: `FiscalYearReset` class supporting custom fiscal calendars (configurable start month/day)
- **Business Day Reset**: `BusinessDayReset` class that skips weekends and holidays
- **Extended ResetType Enum**: Added `CUSTOM` case to support custom reset strategies
- **Database Support**: Added `reset_strategy_class` and `reset_strategy_config` columns to `serial_sequences` table
- **Custom Segments**: Fiscal year segments (`{fiscal_year}`) for pattern integration
- **Comprehensive Tests**: 15 new tests covering fiscal year boundaries, leap years, business day calculations, and holiday handling

#### 📊 Enhanced Audit Logging
- **Spatie Activity Log Integration**: Full integration with `spatie/laravel-activitylog` package
- **Centralized Logging Trait**: `LogsSerialActivity` trait for consistent activity logging
- **Automatic Activity Tracking**: Logs serial generation, voiding, and sequence resets
- **Tenant Support**: Automatic tenant_id tracking for multi-tenant applications
- **Rich Context**: Stores pattern name, user, model type/id, and custom properties
- **Activity Timeline**: Query full history of serial operations across the application
- **Configuration**: Activity logging can be enabled/disabled via config

#### 🌐 RESTful API Endpoints
- **Serial Number Controller**: `SerialNumberController` with 5 RESTful endpoints
- **Generate Endpoint**: `POST /api/v1/serial-numbers/generate` - Generate new serial numbers
- **Preview Endpoint**: `GET /api/v1/serial-numbers/{type}/peek` - Preview next serial without generation
- **Reset Endpoint**: `POST /api/v1/serial-numbers/{type}/reset` - Reset sequence counter
- **Void Endpoint**: `POST /api/v1/serial-numbers/{serial}/void` - Void existing serial
- **Logs Query Endpoint**: `GET /api/v1/serial-numbers/logs` - Query audit logs with pagination and filters
- **API Resources**: `SerialLogResource` and `SerialSequenceResource` for consistent JSON responses
- **Rate Limiting**: Custom `RateLimitSerialGeneration` middleware with per-pattern limits
- **Authentication**: Laravel Sanctum integration for token-based authentication
- **API Configuration**: Configurable API prefix, middleware, and rate limits
- **Documentation**: Complete API documentation with curl examples

#### 🧪 Concurrency Stress Testing
- **Comprehensive Stress Test Suite**: 7 stress tests for high-concurrency scenarios
- **Concurrent Generation Tests**: Tests for 10, 50, and 100+ simultaneous serial generations
- **Uniqueness Validation**: Ensures no collisions under heavy concurrent load
- **Performance Benchmarks**: Validates generation time under stress (< 5 seconds for 100 serials)
- **Memory Profiling**: Monitors memory usage during high-volume operations
- **Reset Stress Tests**: Tests concurrent resets with race condition detection
- **Pattern Isolation**: Verifies different patterns generate serials independently
- **Test Group**: Organized under `@group stress` for optional execution

### Changed
- **SerialSequence Model**: Enhanced with `evaluateCustomResetStrategy()` and `getResetStrategy()` methods
- **SerialManager**: Integrated activity logging and custom reset strategy support
- **Configuration**: Extended `config/serial-pattern.php` with `api` and `activity_log` sections
- **Test Coverage**: Increased from 56 tests to 71 tests with 163 assertions
- **Service Provider**: Now registers API routes when enabled in config

### Dependencies
- Added `spatie/laravel-activitylog` ^4.8
- Added `laravel/sanctum` ^4.0
- Added `illuminate/routing` ^11.0|^12.0
- Added `illuminate/http` ^11.0|^12.0

### Documentation
- **API_DOCUMENTATION.md**: Complete REST API reference with authentication, endpoints, and examples
- **CUSTOM_RESET_STRATEGIES.md**: Guide for implementing and using custom reset strategies
- **EXAMPLES.md**: Updated with custom reset and API usage examples
- **README.md**: Updated with new features and configuration options

### Tests
- Added `tests/Unit/FiscalYearResetTest.php` (5 tests)
- Added `tests/Unit/BusinessDayResetTest.php` (5 tests)
- Added `tests/Feature/CustomResetStrategyTest.php` (5 tests)
- Added `tests/Stress/ConcurrencyStressTest.php` (7 tests)

### Notes
- Stress tests are marked to skip in package environment (require full Laravel application with Cache facade)
- Custom reset strategies require `reset` type to be set to `ResetType::CUSTOM`
- API endpoints can be disabled via `SERIAL_API_ENABLED=false` in `.env`
- Activity logging can be disabled via config if Spatie Activity Log package is not installed

---

## [1.0.0-rc1] - 2025-11-10

### Added
- Initial pre-release candidate
- Pattern-based serial number generation
- Support for dynamic segments (year, month, day, hour, etc.)
- Support for model property segments (e.g., {department.code})
- Configurable reset rules (daily, weekly, monthly, yearly, interval, never)
- Comprehensive audit logging with user tracking
- Serial voiding functionality
- Deletion prevention for audit trail integrity
- Uniqueness enforcement with collision detection
- Concurrency handling with atomic locks
- Eloquent model integration via HasSerialNumbering trait
- Preview functionality for serial numbers
- Manual sequence reset capability
- Export audit logs to CSV and JSON
- Pattern validation Artisan command
- Query scopes for filtering serial logs
- Custom segment resolver support
- Comprehensive test suite (56 tests, 139 assertions)
- Full documentation with examples

### Security
- Atomic locks to prevent race conditions
- Immutable audit logs (deletion prevention)
- Unique serial number enforcement

### Testing
- PHPUnit 10.5 integration
- Unit tests for all core services
- Feature tests for model integration
- ~44% code coverage baseline

---

## [1.0.0] - 2024-10-31

### Added
- Initial release
- Pattern-based serial number generation
- Support for dynamic segments (year, month, day, hour, etc.)
- Support for model property segments (e.g., {department.code})
- Configurable reset rules (daily, weekly, monthly, yearly, interval, never)
- Comprehensive audit logging with user tracking
- Serial voiding functionality
- Deletion prevention for audit trail integrity
- Uniqueness enforcement with collision detection
- Concurrency handling with atomic locks
- Eloquent model integration via HasSerialNumbering trait
- Preview functionality for serial numbers
- Manual sequence reset capability
- Export audit logs to CSV and JSON
- Pattern validation Artisan command
- Query scopes for filtering serial logs
- Custom segment resolver support
- Comprehensive test suite
- Full documentation

### Security
- Atomic locks to prevent race conditions
- Immutable audit logs (deletion prevention)
- Unique serial number enforcement
