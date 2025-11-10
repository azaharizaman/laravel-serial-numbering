# Changelog

All notable changes to `laravel-serial-pattern` will be documented in this file.

## [Unreleased]

### Planned for v1.1.0
- Custom fiscal year reset logic
- Enhanced audit logging with Spatie Activity Log
- Multi-tenant support improvements

### Planned for v1.2.0
- RESTful API endpoints for serial generation
- High-concurrency stress tests
- Performance optimization benchmarks

### Planned for v2.0.0
- Blockchain verification for critical serials
- Advanced compliance features

See [ROADMAP.md](ROADMAP.md) for detailed feature planning.

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
