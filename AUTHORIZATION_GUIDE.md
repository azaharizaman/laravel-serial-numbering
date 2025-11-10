# Authorization Implementation Guide

This guide provides examples for implementing authorization checks for serial number operations.

## Overview

The Laravel Serial Numbering package **does not include built-in authorization** by default. This is intentional to give you full control over your application's authorization logic. This guide shows how to implement authorization using Laravel's built-in features.

---

## Option 1: Using Laravel Gates

### Step 1: Define Gates

Add gates in your `AuthServiceProvider`:

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPolicies();

        // Serial generation authorization
        Gate::define('generate-serial', function ($user, $patternType) {
            // Example: Check if user has permission for this pattern
            return $user->hasPermission("serial.generate.{$patternType}");
        });

        // Sequence reset authorization (admin only)
        Gate::define('reset-serial-sequence', function ($user, $patternType) {
            return $user->hasRole('admin');
        });

        // Serial voiding authorization
        Gate::define('void-serial', function ($user, $serial) {
            // Example: Check if user can void serials
            return $user->hasPermission('serial.void');
        });

        // Serial log viewing authorization
        Gate::define('view-serial-logs', function ($user) {
            return $user->hasRole(['admin', 'auditor']);
        });
    }
}
```

### Step 2: Apply in Controllers

Create a custom controller that wraps the package's functionality:

```php
<?php

namespace App\Http\Controllers\Api;

use AzahariZaman\ControlledNumber\Services\SerialManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuthorizedSerialController extends Controller
{
    public function __construct(
        protected SerialManager $serialManager
    ) {}

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'model_type' => 'nullable|string',
            'model_id' => 'nullable|integer',
        ]);

        // Check authorization
        if (!Gate::allows('generate-serial', $validated['type'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to generate serials for this pattern',
            ], 403);
        }

        // Proceed with generation
        $serial = $this->serialManager->generate(
            $validated['type'],
            $this->resolveModel($validated)
        );

        return response()->json([
            'success' => true,
            'data' => ['serial' => $serial],
        ]);
    }

    public function reset(Request $request, string $type)
    {
        // Check authorization
        $this->authorize('reset-serial-sequence', $type);

        $validated = $request->validate([
            'start_value' => 'nullable|integer|min:0',
        ]);

        $result = $this->serialManager->resetSequence(
            $type,
            $validated['start_value'] ?? null
        );

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Sequence reset successfully' : 'Sequence not found',
        ]);
    }

    public function void(Request $request, string $serial)
    {
        $this->authorize('void-serial', $serial);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->serialManager->void($serial, $validated['reason'] ?? null);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Serial voided successfully' : 'Serial not found',
        ]);
    }

    protected function resolveModel(array $data): ?\Illuminate\Database\Eloquent\Model
    {
        if (empty($data['model_type']) || empty($data['model_id'])) {
            return null;
        }

        $modelClass = $data['model_type'];
        
        // Validate against whitelist
        $allowedModels = config('serial-pattern.api.allowed_models', []);
        if (empty($allowedModels) || !in_array($modelClass, $allowedModels, true)) {
            abort(400, 'Invalid model type');
        }

        return $modelClass::findOrFail($data['model_id']);
    }
}
```

### Step 3: Register Custom Routes

Replace or extend the package routes:

```php
<?php

// routes/api.php

use App\Http\Controllers\Api\AuthorizedSerialController;

Route::prefix('api/v1/serial-numbers')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {
        Route::post('generate', [AuthorizedSerialController::class, 'generate']);
        Route::post('{type}/reset', [AuthorizedSerialController::class, 'reset']);
        Route::post('{serial}/void', [AuthorizedSerialController::class, 'void']);
    });
```

---

## Option 2: Using Laravel Policies

### Step 1: Create Policy

```bash
php artisan make:policy SerialPolicy
```

### Step 2: Define Policy Methods

```php
<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SerialPolicy
{
    use HandlesAuthorization;

    public function generate(User $user, string $patternType): bool
    {
        // Check if user has permission for this pattern
        return $user->hasPermission("serial.generate.{$patternType}");
    }

    public function reset(User $user, string $patternType): bool
    {
        // Only admins can reset sequences
        return $user->hasRole('admin');
    }

    public function void(User $user): bool
    {
        // Check if user can void serials
        return $user->hasPermission('serial.void');
    }

    public function viewLogs(User $user): bool
    {
        // Allow admins and auditors to view logs
        return $user->hasAnyRole(['admin', 'auditor']);
    }
}
```

### Step 3: Register Policy

```php
<?php

namespace App\Providers;

use App\Policies\SerialPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        'serial' => SerialPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}
```

### Step 4: Use in Controllers

```php
public function generate(Request $request)
{
    $validated = $request->validate([
        'type' => 'required|string',
    ]);

    // Check authorization using policy
    if (!auth()->user()->can('generate', ['serial', $validated['type']])) {
        abort(403, 'Unauthorized');
    }

    $serial = $this->serialManager->generate($validated['type']);

    return response()->json(['serial' => $serial]);
}
```

---

## Option 3: Middleware-Based Authorization

### Step 1: Create Middleware

```bash
php artisan make:middleware CheckSerialPermission
```

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSerialPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $user = auth()->user();
        
        // Check permission
        if (!$user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return $next($request);
    }
}
```

### Step 2: Register Middleware

```php
<?php

namespace App\Http;

class Kernel extends HttpKernel
{
    protected $middlewareAliases = [
        // ... other middleware
        'serial.permission' => \App\Http\Middleware\CheckSerialPermission::class,
    ];
}
```

### Step 3: Apply to Routes

```php
<?php

// routes/api.php

Route::prefix('api/v1/serial-numbers')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {
        
        Route::post('generate', [Controller::class, 'generate'])
            ->middleware('serial.permission:serial.generate');
            
        Route::post('{type}/reset', [Controller::class, 'reset'])
            ->middleware('serial.permission:serial.reset');
            
        Route::post('{serial}/void', [Controller::class, 'void'])
            ->middleware('serial.permission:serial.void');
    });
```

---

## Option 4: Role-Based Access Control (RBAC)

### Example with Spatie Laravel Permission

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

### Define Permissions

```php
<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Create permissions
Permission::create(['name' => 'generate-invoice-serials']);
Permission::create(['name' => 'generate-order-serials']);
Permission::create(['name' => 'reset-serial-sequences']);
Permission::create(['name' => 'void-serials']);
Permission::create(['name' => 'view-serial-logs']);

// Create roles and assign permissions
$admin = Role::create(['name' => 'admin']);
$admin->givePermissionTo(Permission::all());

$accountant = Role::create(['name' => 'accountant']);
$accountant->givePermissionTo([
    'generate-invoice-serials',
    'void-serials',
    'view-serial-logs',
]);

$clerk = Role::create(['name' => 'clerk']);
$clerk->givePermissionTo([
    'generate-order-serials',
]);
```

### Use in Controllers

```php
public function generate(Request $request)
{
    $validated = $request->validate([
        'type' => 'required|string',
    ]);

    $patternType = $validated['type'];
    
    // Check permission based on pattern type
    if (!auth()->user()->hasPermissionTo("generate-{$patternType}-serials")) {
        abort(403, "You don't have permission to generate {$patternType} serials");
    }

    $serial = $this->serialManager->generate($patternType);

    return response()->json(['serial' => $serial]);
}
```

---

## Testing Authorization

### Example Tests

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class SerialAuthorizationTest extends TestCase
{
    /** @test */
    public function unauthorized_user_cannot_generate_serials()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/serial-numbers/generate', [
                'type' => 'invoice',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function authorized_user_can_generate_serials()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('generate-invoice-serials');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/serial-numbers/generate', [
                'type' => 'invoice',
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function only_admin_can_reset_sequences()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Non-admin should fail
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/serial-numbers/invoice/reset');
        $response->assertStatus(403);

        // Admin should succeed
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/serial-numbers/invoice/reset');
        $response->assertStatus(200);
    }
}
```

---

## Best Practices

1. **Principle of Least Privilege**: Grant only the minimum permissions needed
2. **Pattern-Specific Permissions**: Different patterns may require different permission levels
3. **Audit All Changes**: Log all reset and void operations with user context
4. **Rate Limiting**: Apply rate limits per user, not just per IP
5. **Multi-Factor Authentication**: Consider MFA for sensitive operations like sequence resets

---

## Security Checklist

- [ ] Authorization implemented for all API endpoints
- [ ] Tests written for authorization logic
- [ ] Permission documentation provided to users
- [ ] Rate limiting configured per user
- [ ] Audit logging enabled for all sensitive operations
- [ ] Multi-factor authentication considered for admins

---

**Last Updated:** 2025-11-10
