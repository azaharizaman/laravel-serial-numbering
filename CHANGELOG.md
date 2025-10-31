# Changelog

All notable changes to `laravel-serial-pattern` will be documented in this file.

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
