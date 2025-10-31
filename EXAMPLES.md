# Laravel Serial Pattern - Examples

This document provides practical examples of using the Laravel Serial Pattern package.

## Table of Contents

1. [Basic Usage](#basic-usage)
2. [Model Integration](#model-integration)
3. [Custom Segments](#custom-segments)
4. [Reset Strategies](#reset-strategies)
5. [Audit and Logging](#audit-and-logging)
6. [Advanced Patterns](#advanced-patterns)

## Basic Usage

### Simple Invoice Numbering

```php
// Configure in config/serial-pattern.php
'patterns' => [
    'invoice' => [
        'pattern' => 'INV-{year}-{number}',
        'start' => 1,
        'digits' => 5,
        'reset' => 'yearly',
    ],
],

// Generate
$manager = app(SerialManager::class);
$serial = $manager->generate('invoice');
// Result: INV-2024-00001
```

### Purchase Order with Month

```php
'purchase_order' => [
    'pattern' => 'PO-{year}{month}-{number}',
    'start' => 100,
    'digits' => 4,
    'reset' => 'monthly',
],

// Result: PO-202410-0100
```

## Model Integration

### Invoice Model

```php
namespace App\Models;

use Azahari\SerialPattern\Traits\HasSerialNumbering;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasSerialNumbering;

    protected $serialPattern = 'invoice';
    protected $serialColumn = 'invoice_number';
    
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'amount',
        'due_date',
    ];
}
```

```php
// Create invoice - serial generated automatically
$invoice = Invoice::create([
    'customer_id' => 1,
    'amount' => 1500.00,
    'due_date' => now()->addDays(30),
]);

echo $invoice->invoice_number; // INV-2024-00001

// Preview next serial
$next = $invoice->previewSerialNumber();
echo $next; // INV-2024-00002

// Access serial logs
$logs = $invoice->serialLogs;
$activeLog = $invoice->activeSerialLog();

// Void a serial
$invoice->voidSerial('Customer cancelled order');
```

### Order Model with Department

```php
namespace App\Models;

use Azahari\SerialPattern\Traits\HasSerialNumbering;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasSerialNumbering;

    protected $serialPattern = 'order';
    
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
```

```php
// Pattern configuration
'order' => [
    'pattern' => 'ORD-{year}-{department.code}-{number}',
    'start' => 1,
    'digits' => 4,
    'reset' => 'yearly',
],

// Usage
$order = Order::create([
    'department_id' => 5, // Department with code 'SALES'
    'amount' => 2500.00,
]);

echo $order->serial_number; // ORD-2024-SALES-0001
```

## Custom Segments

### Branch Code Resolver

```php
// app/Segments/BranchCodeResolver.php
namespace App\Segments;

use Azahari\SerialPattern\Contracts\SegmentInterface;
use Illuminate\Database\Eloquent\Model;

class BranchCodeResolver implements SegmentInterface
{
    public function resolve(?Model $model = null, array $context = []): string
    {
        $user = auth()->user();
        return $user && $user->branch 
            ? strtoupper($user->branch->code) 
            : 'HQ';
    }

    public function getName(): string
    {
        return 'branch';
    }

    public function validate(): bool
    {
        return true;
    }
}
```

```php
// Register in config/serial-pattern.php
'segments' => [
    'branch' => \App\Segments\BranchCodeResolver::class,
],

// Use in pattern
'receipt' => [
    'pattern' => 'RCP-{branch}-{year}{month}-{number}',
    'start' => 1,
    'digits' => 5,
    'reset' => 'monthly',
],

// Result: RCP-NYC-202410-00001
```

### Project Code Segment

```php
namespace App\Segments;

use Azahari\SerialPattern\Contracts\SegmentInterface;
use Illuminate\Database\Eloquent\Model;

class ProjectCodeResolver implements SegmentInterface
{
    public function resolve(?Model $model = null, array $context = []): string
    {
        if ($model && method_exists($model, 'project')) {
            return $model->project->code ?? 'NONE';
        }
        return $context['project_code'] ?? 'NONE';
    }

    public function getName(): string
    {
        return 'project';
    }

    public function validate(): bool
    {
        return true;
    }
}
```

## Reset Strategies

### Daily Reset for Tickets

```php
'support_ticket' => [
    'pattern' => 'TKT-{year}{month}{day}-{number}',
    'start' => 1,
    'digits' => 3,
    'reset' => 'daily',
],

// Day 1: TKT-20241031-001, TKT-20241031-002
// Day 2: TKT-20241101-001 (reset)
```

### Weekly Reset for Reports

```php
'weekly_report' => [
    'pattern' => 'RPT-{year}W{week}-{number}',
    'start' => 1,
    'digits' => 3,
    'reset' => 'weekly',
],

// Week 43: RPT-2024W43-001
// Week 44: RPT-2024W44-001 (reset)
```

### Custom Interval Reset

```php
'maintenance' => [
    'pattern' => 'MNT-{year}-{number}',
    'start' => 1,
    'digits' => 4,
    'reset' => 'interval',
    'interval' => 90, // Reset every 90 days
],
```

### No Reset for Member IDs

```php
'member' => [
    'pattern' => 'MEM-{year}-{number}',
    'start' => 10000,
    'digits' => 6,
    'reset' => 'never',
],

// Continuous: MEM-2024-010000, MEM-2024-010001, MEM-2025-010002
```

## Audit and Logging

### Query Serial Logs

```php
use Azahari\SerialPattern\Models\SerialLog;

// Get all active serials
$activeSerials = SerialLog::active()->get();

// Get voided serials
$voidedSerials = SerialLog::voided()->get();

// Get serials by pattern
$invoices = SerialLog::forPattern('invoice')
    ->active()
    ->latest()
    ->get();

// Get serials by user
$userSerials = SerialLog::byUser(auth()->id())
    ->whereBetween('generated_at', [
        now()->startOfMonth(),
        now()->endOfMonth(),
    ])
    ->get();

// Complex query
$recentVoided = SerialLog::forPattern('order')
    ->voided()
    ->whereBetween('voided_at', [
        now()->subDays(7),
        now(),
    ])
    ->with('user', 'model')
    ->get();
```

### Export Audit Logs

```php
use Azahari\SerialPattern\Helpers\SerialHelper;

// Export to CSV
$csv = SerialHelper::exportToCsv([
    'pattern' => 'invoice',
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
    'is_void' => false,
]);

file_put_contents('invoice_serials.csv', $csv);

// Export to JSON
$json = SerialHelper::exportToJson([
    'user_id' => auth()->id(),
    'start_date' => now()->startOfYear(),
    'end_date' => now()->endOfYear(),
]);

file_put_contents('my_serials.json', $json);
```

### Pattern Statistics

```php
use Azahari\SerialPattern\Helpers\SerialHelper;

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

// Display in view
foreach (config('serial-pattern.patterns') as $name => $config) {
    $stats = SerialHelper::getPatternStats($name);
    echo "{$name}: {$stats['total']} total, {$stats['void_rate']}% voided\n";
}
```

## Advanced Patterns

### Multi-Entity Pattern

```php
'document' => [
    'pattern' => '{model.type}-{year}-{model.department_id}-{number}',
    'start' => 1,
    'digits' => 5,
    'reset' => 'yearly',
],

// Results vary by model:
// INV-2024-5-00001 (Invoice from dept 5)
// PO-2024-3-00001 (PurchaseOrder from dept 3)
```

### Hierarchical Pattern

```php
'project_task' => [
    'pattern' => '{model.project.code}-{year}-{model.milestone_id}-{number}',
    'start' => 1,
    'digits' => 3,
    'reset' => 'never',
],

// Result: WEBAPP-2024-2-001
```

### Time-Based Pattern

```php
'timestamp_doc' => [
    'pattern' => 'DOC-{year}{month}{day}{hour}{minute}-{number}',
    'start' => 1,
    'digits' => 2,
    'reset' => 'hourly', // Custom reset type
],

// Result: DOC-202410311430-01
```

### Manual Serial Generation with Context

```php
$manager = app(SerialManager::class);

$serial = $manager->generate('invoice', null, [
    'custom_prefix' => 'URGENT',
]);

// With custom segment resolver that reads context
// Result: URGENT-INV-2024-00001
```

## Tips and Best Practices

1. **Pattern Design**
   - Keep patterns readable and meaningful
   - Use consistent prefixes across your application
   - Include date segments for easy filtering and archiving

2. **Reset Strategy**
   - Choose reset frequency based on business needs
   - Monthly reset is common for invoices
   - Daily reset works well for tickets and orders
   - Never reset for permanent IDs (members, accounts)

3. **Model Integration**
   - Always use `$fillable` to include the serial column
   - Consider using database migrations to add indexes
   - Use `shouldGenerateSerial()` for conditional generation

4. **Performance**
   - Enable Redis for caching in high-traffic applications
   - Use appropriate indexes on serial_logs table
   - Consider archiving old serial logs periodically

5. **Security**
   - Never expose internal sequence numbers
   - Use voiding instead of deletion for audit trails
   - Review serial logs regularly for anomalies

6. **Testing**
   - Test serial generation under concurrent loads
   - Verify reset logic with time-travel tests
   - Check uniqueness constraints in tests
