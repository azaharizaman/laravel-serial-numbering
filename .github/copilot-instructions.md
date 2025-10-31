## 🧭 GitHub Copilot Agent Instruction: Laravel Serial Numbering Package with Audit Logging

### 🏷️ Package Name
`azaharizaman/controlled-number`

---

### 🧱 1. Scaffold the Package Structure

Absolutely, Azahari. Here's a **scaffolded folder structure** for your Laravel Composer package `laravel-serial-pattern`, designed to be modular, extensible, and aligned with Laravel 12 best practices. I’ve included relevant folders for factories, observers, events, traits, behaviors, enums, exceptions, helpers, commands, policies, services, casts, and more—whether for current use or future expansion.

---

## 📁 Laravel Package Folder Structure

```
laravel-serial-pattern/
├── config/
│   └── serial-pattern.php                # Configuration file for patterns, logging, etc.
│
├── database/
│   └── migrations/
│       ├── create_serial_sequences_table.php
│       └── create_serial_logs_table.php
│
├── src/
│   ├── Console/
│   │   └── Commands/
│   │       └── ValidatePatternsCommand.php   # Artisan command to validate patterns
│   │
│   ├── Contracts/
│   │   └── SegmentInterface.php              # Interface for segment resolvers
│   │
│   ├── Enums/
│   │   └── ResetType.php                     # Enum for reset types (daily, monthly, etc.)
│   │
│   ├── Events/
│   │   ├── SerialNumberGenerated.php
│   │   └── SerialNumberVoided.php
│   │
│   ├── Exceptions/
│   │   ├── SerialCollisionException.php
│   │   └── SerialDeletionNotAllowedException.php
│   │
│   ├── Helpers/
│   │   └── SerialHelper.php                  # Utility functions for formatting, previewing
│   │
│   ├── Models/
│   │   ├── SerialSequence.php
│   │   └── SerialLog.php
│   │
│   ├── Observers/
│   │   └── SerialLogObserver.php             # Optional observer for audit hooks
│   │
│   ├── Policies/
│   │   └── SerialLogPolicy.php               # Optional policy for viewing logs
│   │
│   ├── Services/
│   │   ├── SerialManager.php                 # Core logic for generation, reset, uniqueness
│   │   ├── SerialPattern.php                 # Pattern parser and validator
│   │   └── SegmentResolver.php               # Resolves dynamic segments
│   │
│   ├── Traits/
│   │   └── HasSerialNumbering.php            # Trait for Eloquent model integration
│   │
│   ├── Behaviors/
│   │   └── Resettable.php                    # Behavior for reset logic (optional)
│   │
│   ├── Casts/
│   │   └── SerialPatternCast.php             # Optional cast for storing pattern config
│   │
│   └── SerialPatternServiceProvider.php      # Registers config, migrations, services
│
├── tests/
│   ├── Feature/
│   │   └── SerialGenerationTest.php
│   ├── Unit/
│   │   ├── PatternParsingTest.php
│   │   └── LoggingTest.php
│
├── composer.json
├── README.md
├── LICENSE
└── .gitignore
```

---

## 🧠 Why These Folders Matter

- **Console/Commands**: For Artisan tools like `serial:validate`, `serial:preview`.
- **Contracts**: Clean abstraction for segment resolvers.
- **Enums**: Strong typing for reset types, void reasons.
- **Events**: Hook into serial lifecycle (e.g., notify when generated or voided).
- **Exceptions**: Custom error handling for collisions, deletion attempts.
- **Helpers**: Centralize formatting, preview, and utility logic.
- **Observers**: Optional audit hooks for model lifecycle.
- **Policies**: Gate access to logs or sensitive serial data.
- **Services**: Core business logic, clean separation from models.
- **Traits**: Reusable logic for Eloquent models.
- **Behaviors**: Optional mixins for reset logic or pattern validation.
- **Casts**: Store pattern config as structured data in DB.

---

```bash
mkdir -p packages/azaharizaman/controlled-number/src
cd packages/azaharizaman/controlled-number

composer init --name="azaharizaman/controlled-number" --description="Configurable serial number generator for Laravel models with optional audit logging" --type="library" --license="MIT" --require="illuminate/support:^10.0" --autoload="psr-4" --autoload-psr4="AzahariZaman\\ControlledNumber\\":"src/"
```

Create the following files and folders:

```bash
# Core service provider and config
touch src/SerialPatternServiceProvider.php
mkdir config && touch config/serial-pattern.php

# Core logic
touch src/SerialManager.php
touch src/SerialPattern.php
touch src/SegmentResolver.php
touch src/Contracts/SegmentInterface.php
touch src/Traits/HasSerialNumbering.php

# Audit logging model
mkdir src/Models && touch src/Models/SerialLog.php

# Migrations
mkdir database/migrations
touch database/migrations/create_serial_sequences_table.php
touch database/migrations/create_serial_logs_table.php

# Tests
mkdir -p tests/Feature && touch tests/Feature/SerialGenerationTest.php
```

---

### 🧩 2. Define Config File (`config/serial-pattern.php`)

```php
return [
    'patterns' => [
        'invoice' => [
            'pattern' => '{year}-{month}-{department.code}-{number}',
            'start' => 1000,
            'digits' => 5,
            'reset' => 'monthly',
            'interval' => 1,
            'delimiters' => ['-', '/'],
        ],
    ],
    'logging' => [
        'enabled' => true,
        'track_user' => true,
    ],
];
```

---

### 🧠 3. Implement Core Classes

- `SerialManager.php`: handles generation, uniqueness, reset logic, and logging
- `SerialPattern.php`: parses and validates segment structure
- `SegmentResolver.php`: resolves `{year}`, `{month}`, `{model.property}`, etc.
- `Contracts/SegmentInterface.php`: interface for pluggable segment types
- `Traits/HasSerialNumbering.php`: trait for Eloquent models to auto-generate serials
- `Models/SerialLog.php`: audit log model with voiding and deletion protection

---

### 🧾 4. Create Migrations

#### `create_serial_sequences_table.php`

```php
Schema::create('serial_sequences', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('pattern');
    $table->unsignedBigInteger('current_number')->default(0);
    $table->string('reset_type')->default('never');
    $table->unsignedInteger('reset_interval')->nullable();
    $table->timestamp('last_reset_at')->nullable();
    $table->timestamps();
});
```

#### `create_serial_logs_table.php`

```php
Schema::create('serial_logs', function (Blueprint $table) {
    $table->id();
    $table->string('serial')->unique();
    $table->string('pattern_name');
    $table->morphs('model');
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
    $table->timestamp('generated_at')->useCurrent();
    $table->timestamp('voided_at')->nullable();
    $table->text('void_reason')->nullable();
    $table->boolean('is_void')->default(false);
    $table->timestamps();
});
```

---

### 🧰 5. Implement `SerialLog` Model

```php
class SerialLog extends Model
{
    protected $fillable = [
        'serial', 'pattern_name', 'model_type', 'model_id',
        'user_id', 'generated_at', 'voided_at', 'void_reason', 'is_void',
    ];

    protected $casts = [
        'is_void' => 'boolean',
        'generated_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function void(string $reason = null): void
    {
        $this->update([
            'is_void' => true,
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);
    }

    public function delete(): void
    {
        throw new \Exception("Serial logs cannot be deleted.");
    }
}
```

---

### 🔌 6. Register Service Provider

```php
public function register()
{
    $this->mergeConfigFrom(__DIR__.'/../config/serial-pattern.php', 'serial-pattern');
}

public function boot()
{
    $this->publishes([
        __DIR__.'/../config/serial-pattern.php' => config_path('serial-pattern.php'),
    ], 'config');

    $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
}
```
--

### 🛠️ 7. Advance feature improvements


---

## 🚀 Functional Enhancements

### a. **Segment Caching**
- Cache resolved segments (e.g., `{user.code}`, `{department.abbr}`) to reduce repeated model lookups.
- Use Laravel’s cache tags for pattern-specific invalidation.

### b. **Custom Segment Providers**
- Allow users to register custom segment resolvers via service container or config:
  ```php
  'segments' => [
      'custom.project_code' => \App\Segments\ProjectCodeResolver::class,
  ]
  ```

### c. **Multi-lingual Segment Support**
- Enable `{month}` or `{day}` segments to be localized (e.g., `Oktober`, `Isnin`) based on app locale.

### d. **Preview Mode**
- Add a method like `previewSerial($pattern, $model)` to simulate the next serial without committing it.

### e. **Pattern Validation CLI**
- Artisan command to validate all configured patterns for uniqueness, reset safety, and segment integrity:
  ```bash
  php artisan serial:validate-patterns
  ```

---

## 🧪 Logging & Audit Improvements

### f. **Log Export**
- Export `serial_logs` to CSV or JSON for audit or reporting purposes.

### g. **Log Filtering API**
- Provide query scopes or API endpoints to filter logs by user, model, date, or void status.

### h. **Soft Void Reasons**
- Standardize void reasons via enum or config (e.g., `duplicate`, `cancelled`, `error`) for analytics.

---

## 🧰 Developer Experience

### i. **Laravel Nova / Filament Integration**
- Optional resource classes for managing patterns, logs, and previewing serials in admin panels.

### j. **Testbench Integration**
- Run test with phpunit 

### k. **Pattern Discovery**
- Auto-discover patterns from config or database and register them dynamically.

---

## 🧱 Architectural Improvements

### l. **Database Driver Abstraction**
- Support PostgreSQL sequences or Redis counters for high-throughput environments.

### m. **Concurrency Locking**
- Use Laravel’s atomic locks to prevent race conditions during serial generation.

### n. **Multi-Tenant Support**
- Add tenant-aware serial generation using Laravel’s tenancy packages (e.g., `tenancy/tenancy`).

---

## 🌍 Ecosystem & Community

### o. **Packagist Publishing**
- Make it installable via Composer:
  ```bash
  composer require azaharizaman/controlled-number
  ```

### p. **GitHub Actions CI**
- Add automated tests, linting, and release tagging.

### q. **Documentation Site**
- Host docs via GitHub Pages or Laravel Jigsaw with examples, API reference, and migration guides.


### 🧪 8. Add Tests

- Test serial generation with and without logging
- Test uniqueness enforcement
- Test reset behavior
- Test voiding and audit trail
- Test trait integration with Eloquent models
- Test custom segment resolvers
- Test edge cases (e.g., high concurrency, invalid patterns)
- Use Pest or PHPUnit for testing
---

### 🚀 9. Finalize & Publish

- Add `README.md` with usage examples
- Add `LICENSE` (MIT)
- Tag version `v1.0.0`
- Push to GitHub
- Optionally publish to [Packagist](https://packagist.org/)

---

## 📚 Essential Documentation for Laravel Package Development

### 🧱 Laravel 12 Package Development
- **Laravel Official Guide**:  
  [Package Development – Laravel 12.x](https://laravel.com/docs/12.x/packages)  
  Covers service providers, configuration, migrations, publishing assets, and package discovery.

- **Step-by-Step Tutorial**:  
  [CodeHunger – How to Create Custom Packages in Laravel 12](https://www.codehunger.in/blog/how-to-create-custom-packages-in-laravel-12-a-complete-step-by-step-guide)  
  Includes directory structure, service provider setup, migrations, and Packagist submission.

- **Quick Reference**:  
  [Stack Overflow – Quick Guide to Laravel Package Development](https://stackoverflow.com/collectives/php/articles/79585356/quick-guide-to-laravel-package-development)  
  Summarizes package anatomy, publishing, and testing.

---

## 🧰 Composer Package Guidelines

- **Composer Official Docs**:  
  [Composer Documentation](https://getcomposer.org/doc/)  
  Learn about `composer.json`, autoloading, versioning, and publishing.

- **Packagist Submission Guide**:  
  [Publishing to Packagist](https://packagist.org/about)  
  Explains how to submit your package and manage releases.

- **Best Practices**:
  - Use **PSR-4 autoloading**
  - Include a clear **README.md**
  - Add a **LICENSE** (MIT recommended)
  - Tag releases with semantic versioning (`v1.0.0`, `v1.1.0`, etc.)
  - Use **GitHub Actions** for testing

---

## 🧪 Testing Tools


- **Pest PHP**:  
  [Pest Testing Framework](https://pestphp.com/docs/introduction)  
  Elegant alternative to PHPUnit for Laravel packages.

---

## 🧭 Suggested Workflow

1. **Scaffold your package** using Laravel’s structure
2. **Write your service provider** and register config/migrations
3. **Implement core logic** (serial manager, trait, logging)
4. **Write tests** using Pest or PHPUnit
5. **Publish to GitHub**, tag release, and submit to Packagist
6. **Document usage** in README with examples and installation steps

---