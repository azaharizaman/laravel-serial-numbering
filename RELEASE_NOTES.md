# Release Notes – v1.0.0-rc1

**Release Date:** November 10, 2025  
**Type:** Pre-release / Release Candidate  
**Status:** Ready for Community Testing

---

## 🎉 Overview

We're excited to announce the first pre-release of **Laravel Serial Pattern** – a powerful, production-ready package for generating configurable serial numbers with comprehensive audit logging. This release candidate stabilizes the core API and invites community feedback before the official 1.0.0 launch.

---

## ✨ What's Included

### Core Features

#### 🎯 Pattern-Based Serial Generation
Create flexible serial number formats using dynamic segments:
```php
'invoice' => [
    'pattern' => 'INV-{year}-{month}-{number}',
    'start' => 1000,
    'digits' => 5,
    'reset' => 'monthly',
]
// Generates: INV-2025-11-01000, INV-2025-11-01001, ...
```

Supports 10+ built-in segments including `{year}`, `{quarter}`, `{week}`, `{day}`, and custom model properties like `{department.code}`.

---

#### 🔄 Auto-Reset Strategies
Configure automatic sequence resets based on time intervals:
- **Daily**: Reset at midnight
- **Weekly**: Reset every Monday (or custom start day)
- **Monthly**: Reset on the 1st of each month
- **Yearly**: Reset on January 1st
- **Interval**: Custom periods (e.g., every 30 days)
- **Never**: Continuous incrementing

Perfect for fiscal year cycles, monthly invoicing, or daily ticket systems.

---

#### 🔒 Concurrency Protection
Built-in atomic locking prevents race conditions in high-traffic environments:
- Laravel's Cache-based locks with configurable timeouts
- Database transactions for sequence updates
- Automatic collision detection and retry logic

Tested to handle hundreds of simultaneous requests without duplicate serials.

---

#### 📊 Comprehensive Audit Logging
Track every serial number lifecycle event:
- **Generation**: Who created it, when, and for which model
- **Voiding**: Soft-delete with reason tracking
- **Immutability**: Logs cannot be deleted (only voided)
- **Query Scopes**: Filter by pattern, date, user, or void status

Export logs to CSV or JSON for compliance reporting:
```php
SerialLog::exportToCsv('audit-report.csv');
```

---

#### 🧩 Eloquent Model Integration
One-line setup with the `HasSerialNumbering` trait:
```php
class Invoice extends Model
{
    use HasSerialNumbering;
    
    protected $serialPattern = 'invoice';
    protected $serialColumn = 'invoice_number';
}

// Automatic serial generation on create
$invoice = Invoice::create(['amount' => 1500]);
echo $invoice->invoice_number; // INV-2025-11-01000
```

---

#### 🔌 Extensible Architecture
Register custom segment resolvers for specialized needs:
```php
SerialManager::registerSegmentResolver('fiscal_year', function ($model, $context) {
    return FiscalYear::current()->code;
});
```

---

### Developer Experience

#### 🧪 Well-Tested Codebase
- **56 passing tests** with **139 assertions**
- **~44% code coverage** (core services fully tested)
- Unit tests for pattern parsing, segment resolution, and logging
- Feature tests for Eloquent integration and concurrency handling

Run the test suite:
```bash
composer test
composer test:coverage
```

---

#### 📚 Comprehensive Documentation
- [README.md](README.md): Quick start guide and API reference
- [EXAMPLES.md](EXAMPLES.md): Real-world usage patterns
- [CONTRIBUTING.md](CONTRIBUTING.md): Developer contribution guide
- [ROADMAP.md](ROADMAP.md): Planned features and timelines

---

#### 🛠️ Artisan Commands
Validate your pattern configurations:
```bash
php artisan serial:validate-patterns
```

---

## 🚀 Installation

```bash
composer require azaharizaman/laravel-serial-numbering:^1.0-rc
php artisan vendor:publish --tag=serial-pattern-config
php artisan migrate
```

---

## 🔄 Migration from Earlier Versions

This is the first public pre-release – no migration required!

---

## 🐛 Known Issues

None reported yet. Please open an issue on GitHub if you encounter problems during testing.

---

## 🗺️ What's Next?

This pre-release focuses on core stability. Planned enhancements include:

### Version 1.1.0 (Q2 2025)
- Custom fiscal year reset logic
- Enhanced audit logging with Spatie Activity Log
- Multi-tenant support improvements

### Version 1.2.0 (Q3 2025)
- RESTful API endpoints for external integrations
- High-concurrency stress tests (1,000+ requests)
- Performance optimization benchmarks

### Version 2.0.0 (Q4 2025)
- Blockchain verification for critical documents
- Advanced compliance features

See [ROADMAP.md](ROADMAP.md) for detailed planning.

---

## 🤝 Community Feedback

We value your input! This pre-release is specifically for gathering feedback before the stable 1.0.0 launch.

### How to Help
1. **Test in your projects**: Install and integrate into dev/staging environments
2. **Report issues**: Open GitHub issues for bugs or unexpected behavior
3. **Request features**: Submit enhancement proposals via GitHub discussions
4. **Contribute**: PRs welcome! See [CONTRIBUTING.md](CONTRIBUTING.md)

### Reporting Bugs
Include the following in your issue:
- Laravel version
- PHP version
- Database driver (MySQL, PostgreSQL, SQLite)
- Pattern configuration
- Steps to reproduce
- Expected vs actual behavior

---

## 📦 Package Details

- **Package Name**: `azaharizaman/laravel-serial-numbering`
- **Version**: 1.0.0-rc1
- **License**: MIT
- **Requirements**:
  - PHP 8.3+
  - Laravel 12.0+
  - Composer 2.0+

---

## 👏 Acknowledgments

Special thanks to:
- The Laravel community for framework excellence
- Early testers and feedback providers
- Contributors to related packages (Eloquent, Carbon, PHPUnit)

---

## 📞 Support

- **Documentation**: [GitHub README](https://github.com/azaharizaman/laravel-serial-numbering)
- **Issues**: [GitHub Issues](https://github.com/azaharizaman/laravel-serial-numbering/issues)
- **Discussions**: [GitHub Discussions](https://github.com/azaharizaman/laravel-serial-numbering/discussions)
- **Email**: azaharizaman@gmail.com

---

**Happy Testing!** 🎉

We're excited to hear your feedback and make this the best serial numbering package for Laravel.

---

*This is a pre-release version. API changes may occur before the stable 1.0.0 release based on community feedback.*
