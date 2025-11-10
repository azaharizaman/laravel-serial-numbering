# Coding Guidelines

This document outlines coding standards, security best practices, and lessons learned from code reviews to maintain high code quality and prevent common mistakes in the Laravel Serial Numbering package.

---

## 🔒 Security Best Practices

### 1. **Prevent Arbitrary Class Instantiation**

**Problem:** Allowing user input to directly instantiate classes creates a security vulnerability where attackers could instantiate sensitive classes or trigger unwanted side effects.

**Bad Example:**
```php
$modelClass = $request->input('model_type');
$model = $modelClass::find($id); // Dangerous!
```

**Good Example:**
```php
// Validate class exists and is an Eloquent model
if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
    throw new \InvalidArgumentException('Invalid model type');
}

// Check against whitelist if configured
$allowedModels = config('serial-pattern.api.allowed_models', []);
if (!empty($allowedModels) && !in_array($modelClass, $allowedModels, true)) {
    throw new \InvalidArgumentException('Model type not allowed');
}

$model = $modelClass::findOrFail($id);
```

**Implementation:**
- Always validate class existence with `class_exists()`
- Verify inheritance with `is_subclass_of()` or `instanceof`
- Use whitelists for allowed classes in configuration
- Apply strict type checking with `in_array($value, $array, true)`

---

### 2. **Sanitize Exception Messages in API Responses**

**Problem:** Exposing raw exception messages can leak sensitive information about application internals, file paths, database schema, or implementation details.

**Bad Example:**
```php
} catch (\Exception $e) {
    return response()->json([
        'success' => false,
        'message' => $e->getMessage(), // Dangerous!
    ], 400);
}
```

**Good Example:**
```php
} catch (\InvalidArgumentException $e) {
    // Safe to expose validation errors
    return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
    ], 400);
    
} catch (\Exception $e) {
    // Log details for debugging
    \Log::error('Operation failed', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    // Return generic message
    return response()->json([
        'success' => false,
        'message' => 'An error occurred while processing your request',
    ], 400);
}
```

**Implementation:**
- Log full exception details internally using Laravel's logging
- Return generic error messages to API consumers
- Only expose specific exceptions that are safe (e.g., validation errors)
- Use appropriate HTTP status codes (400, 404, 500, etc.)

---

### 3. **Prevent IP Spoofing in Rate Limiting**

**Problem:** Using `$request->ip()` alone can be easily spoofed via `X-Forwarded-For` headers when behind a proxy or load balancer.

**Bad Example:**
```php
$key = 'rate_limit:' . $request->ip(); // Can be spoofed!
```

**Good Example:**
```php
// Use getClientIp() with fallback
$clientIp = $request->getClientIp() ?: $request->ip();
$key = 'rate_limit:' . $clientIp;
```

**Important Notes:**
- Ensure Laravel's `TrustProxies` middleware is properly configured
- Add trusted proxy IP addresses to the middleware
- Document proxy configuration requirements in deployment guides
- Consider combining IP with user ID for authenticated requests

---

### 4. **CSRF Protection for State-Changing Operations**

**Problem:** API endpoints using session-based authentication are vulnerable to CSRF attacks if not properly protected.

**Solution:**
```php
// routes/api.php
Route::middleware(['api', 'auth:sanctum'])
    ->group(function () {
        Route::post('generate', [Controller::class, 'generate']);
    });
```

**Best Practices:**
- Use token-based authentication (Sanctum) for API routes
- If supporting session auth, add CSRF middleware to POST/PUT/DELETE routes
- Document that API routes are token-only, not session-based
- Separate web and API authentication strategies clearly

---

### 5. **Authorization and Policy Checks**

**Problem:** Missing authorization allows any authenticated user to perform sensitive operations.

**Implementation Required:**
```php
public function reset(Request $request, string $type): JsonResponse
{
    // Add authorization check
    $this->authorize('reset-serial-sequence', $type);
    
    // Or use Gate
    if (!Gate::allows('reset-serial-sequence', $type)) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 403);
    }
    
    // ... rest of the method
}
```

**Recommendation:**
- Define policies for all sensitive operations
- Document required permissions in API documentation
- Provide examples of policy implementations for users

---

## 🧩 Code Quality Best Practices

### 6. **Handle Optional Dependencies Gracefully**

**Problem:** Requiring packages that users may not need creates unnecessary dependencies.

**Bad Example:**
```json
{
  "require": {
    "spatie/laravel-activitylog": "^4.8"
  }
}
```

**Good Example:**
```json
{
  "require": {
    "illuminate/support": "^12.0"
  },
  "suggest": {
    "spatie/laravel-activitylog": "Required for enhanced activity logging (^4.8)"
  }
}
```

**Implementation:**
```php
protected function logActivity(string $description, array $properties = []): void
{
    // Check if Spatie Activity Log is available
    if (class_exists(\Spatie\Activitylog\Facades\Activity::class)) {
        $this->logWithSpatie($description, $properties);
        return;
    }
    
    // Fallback to Laravel's default logger
    $this->logWithLaravel($description, $properties);
}
```

**Principles:**
- Use `class_exists()` to detect optional packages
- Provide fallback implementations using Laravel core features
- Document optional dependencies in README
- Set feature flags to `false` by default in config

---

### 7. **DRY Principle - Extract Duplicate Logic**

**Problem:** Duplicated code leads to maintenance issues and inconsistencies.

**Bad Example:**
```php
public function methodA() {
    if (!empty($data['model_type'])) {
        $modelClass = $data['model_type'];
        if (class_exists($modelClass)) {
            $model = $modelClass::findOrFail($data['model_id']);
        }
    }
}

public function methodB() {
    if (!empty($data['model_type'])) {
        $modelClass = $data['model_type'];
        if (class_exists($modelClass)) {
            $model = $modelClass::findOrFail($data['model_id']);
        }
    }
}
```

**Good Example:**
```php
private function resolveModel(array $data): ?Model
{
    if (empty($data['model_type']) || empty($data['model_id'])) {
        return null;
    }
    
    $modelClass = $data['model_type'];
    
    if (!class_exists($modelClass)) {
        throw new \InvalidArgumentException('Model class does not exist');
    }
    
    return $modelClass::findOrFail($data['model_id']);
}

public function methodA() {
    $model = $this->resolveModel($data);
}

public function methodB() {
    $model = $this->resolveModel($data);
}
```

---

### 8. **Validate Constructor Parameters**

**Problem:** Invalid date configurations can cause runtime errors or unexpected behavior.

**Bad Example:**
```php
public function __construct(int $startMonth, int $startDay)
{
    $this->startMonth = max(1, min(12, $startMonth));
    $this->startDay = max(1, min(31, $startDay));
    // No validation - February 31st is accepted!
}
```

**Good Example:**
```php
public function __construct(int $startMonth = 4, int $startDay = 1)
{
    $this->startMonth = max(1, min(12, $startMonth));
    $this->startDay = max(1, min(31, $startDay));
    
    // Validate the date is actually possible
    if (!checkdate($this->startMonth, $this->startDay, 2024)) {
        throw new \InvalidArgumentException(
            "Invalid date: {$this->startMonth}/{$this->startDay}"
        );
    }
}
```

---

### 9. **Prevent Infinite Loops**

**Problem:** Loops without exit conditions can hang the application indefinitely.

**Bad Example:**
```php
protected function findBusinessDay(\DateTime $date): \DateTime
{
    while ($this->isNonBusinessDay($date)) {
        $date->modify('-1 day');
        // What if all days are non-business days?
    }
    return $date;
}
```

**Good Example:**
```php
protected function findBusinessDay(\DateTime $date): \DateTime
{
    $maxIterations = 365;
    $iterations = 0;
    
    while ($this->isNonBusinessDay($date) && $iterations < $maxIterations) {
        $date->modify('-1 day');
        $iterations++;
    }
    
    if ($iterations >= $maxIterations) {
        throw new \RuntimeException('Could not find a business day within 365 days');
    }
    
    return $date;
}
```

---

### 10. **Configurable Dependency Resolution**

**Problem:** Hard-coded checks for global functions create tight coupling and fragile code.

**Bad Example:**
```php
if (function_exists('tenant') && tenant()) {
    $properties['tenant_id'] = tenant()->id;
}
```

**Good Example:**
```php
// In config
'tenant_resolver' => null, // or a callable

// In code
protected function resolveTenantId()
{
    // Use custom resolver if configured
    $resolver = config('serial-pattern.logging.activity_log.tenant_resolver');
    if ($resolver && is_callable($resolver)) {
        return $resolver();
    }
    
    // Try container binding
    if (app()->bound('tenant')) {
        $tenant = app('tenant');
        if ($tenant && isset($tenant->id)) {
            return $tenant->id;
        }
    }
    
    // Try global function as last resort
    if (function_exists('tenant') && tenant()) {
        return tenant()->id;
    }
    
    return null;
}
```

**Benefits:**
- Users can provide custom resolvers
- More testable (mock the callable)
- No assumption about package implementation
- Graceful fallbacks

---

## 📝 Configuration Best Practices

### 11. **Laravel Configuration Conventions**

**Naming:**
- Use `snake_case` for configuration keys
- Use `camelCase` for PHP class properties
- Use descriptive, unambiguous names

**Structure:**
```php
return [
    // Group related settings
    'api' => [
        'enabled' => false,
        'allowed_models' => [],
        'rate_limit' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
    ],
    
    // Use environment variables for deployment-specific settings
    'enabled' => env('SERIAL_API_ENABLED', false),
    
    // Provide sensible defaults
    'logging' => [
        'enabled' => true,
        'activity_log' => [
            'enabled' => false, // Optional feature off by default
        ],
    ],
];
```

**Indentation:**
- Use 4 spaces for indentation
- Maintain consistent alignment
- Use proper array formatting

---

## 🧪 Testing Best Practices

### 12. **Test Security Features**

Always write tests for security-related functionality:

```php
/** @test */
public function it_rejects_invalid_model_classes()
{
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid model type');
    
    $controller->resolveModel([
        'model_type' => 'NotAModel',
        'model_id' => 1,
    ]);
}

/** @test */
public function it_rejects_models_not_in_whitelist()
{
    config(['serial-pattern.api.allowed_models' => [Invoice::class]]);
    
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Model type not allowed');
    
    $controller->resolveModel([
        'model_type' => Order::class,
        'model_id' => 1,
    ]);
}
```

---

## 📚 Documentation Requirements

### 13. **Document Security Implications**

In README or API documentation:

```markdown
## Security Considerations

### Model Whitelist
When using the API, configure allowed models to prevent arbitrary class instantiation:

```php
// config/serial-pattern.php
'api' => [
    'allowed_models' => [
        \App\Models\Invoice::class,
        \App\Models\Order::class,
    ],
],
```

### Proxy Configuration
If running behind a proxy or load balancer, configure trusted proxies:

```php
// app/Http/Middleware/TrustProxies.php
protected $proxies = ['192.168.1.1', '10.0.0.0/8'];
```

### Authorization
Implement authorization policies for sensitive operations:

```php
Gate::define('reset-serial-sequence', function ($user, $type) {
    return $user->hasPermission('manage-serials');
});
```
```

---

## 🎯 Summary

**Key Takeaways:**

1. **Security First**: Always validate user input, sanitize outputs, and implement proper authorization
2. **Optional Dependencies**: Make non-essential packages optional with graceful fallbacks
3. **Configuration**: Follow Laravel conventions, provide defaults, use environment variables
4. **Error Handling**: Log internally, return generic messages externally
5. **Code Quality**: Follow DRY, validate inputs, prevent edge cases
6. **Documentation**: Document security requirements, optional features, and configuration

**Before Merging:**
- [ ] Run all tests
- [ ] Review security implications
- [ ] Update documentation
- [ ] Add changelog entry
- [ ] Test with and without optional dependencies

---

**Last Updated:** 2025-11-10  
**Version:** 1.1.0
