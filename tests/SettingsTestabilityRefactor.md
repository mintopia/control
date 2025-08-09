# Improving Testability for Setting::fetch() Usage

## Problem

The current codebase uses static calls to `App\Models\Setting::fetch()` throughout the application. This makes it difficult to mock or stub the settings logic in unit tests, leading to brittle tests that may hit the database or require complex mocking.

## Solution: Use Dependency Injection for Settings

To improve testability and maintainability, we recommend refactoring the code to use dependency injection for settings access. This involves:

1. **Create a Settings Repository Interface**
   - Define an interface (e.g., `SettingRepositoryInterface`) that exposes a `fetch` method.

2. **Create a Concrete Implementation**
   - Implement the interface in a class (e.g., `SettingRepository`) that delegates to the current static method.

3. **Bind the Interface to the Implementation**
   - In a service provider (e.g., `AppServiceProvider`), bind the interface to the implementation using Laravel's service container.

4. **Inject the Interface Where Needed**
   - In classes that need settings (e.g., form requests, controllers, services), inject the interface via the constructor or setter injection.

5. **Mock the Interface in Tests**
   - In tests, bind a mock implementation to the interface, allowing you to control the return values for different scenarios.

## Example

### 1. Interface

```php
// app/Contracts/SettingRepositoryInterface.php
namespace App\Contracts;

interface SettingRepositoryInterface
{
    public function fetch(string $code, $default = null);
}
```

### 2. Implementation

```php
// app/Repositories/SettingRepository.php
namespace App\Repositories;

use App\Contracts\SettingRepositoryInterface;
use App\Models\Setting;

class SettingRepository implements SettingRepositoryInterface
{
    public function fetch(string $code, $default = null)
    {
        return Setting::fetch($code, $default);
    }
}
```

### 3. Service Provider Binding

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->bind(
        \App\Contracts\SettingRepositoryInterface::class,
        \App\Repositories\SettingRepository::class
    );
}
```

### 4. Usage in Application Code

```php
// Example: Inject into a Form Request
use App\Contracts\SettingRepositoryInterface;

class UserSignupRequest extends FormRequest
{
    protected $settings;

    public function __construct(SettingRepositoryInterface $settings)
    {
        parent::__construct();
        $this->settings = $settings;
    }

    // ... use $this->settings->fetch('terms') ...
}
```

### 5. Usage in Tests

```php
// In your test method or setUp()
$mock = Mockery::mock(SettingRepositoryInterface::class);
$mock->shouldReceive('fetch')->with('terms')->andReturn(true);
$this->app->instance(SettingRepositoryInterface::class, $mock);
```

## Benefits

- **Easier to test:** No more static method mocking or database hits in unit tests.
- **Cleaner code:** Dependencies are explicit and can be swapped easily.
- **More maintainable:** Future changes to settings logic only require changes in one place.

---

*Discuss with the team before implementation. This pattern is a Laravel best practice for testable, maintainable code.*
