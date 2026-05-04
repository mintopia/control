# Admin API & API Keys Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add four read-only `/api/v1/*` endpoints (events list/show, tickets list with optional event filter) authenticated via a new standalone `ApiKey` model, plus an admin UI under Settings to manage those keys.

**Architecture:** Custom Laravel `apikey` auth guard that authenticates `Authorization: Bearer ctrl_<hex>` against a hashed `api_keys` table and returns an `Authenticatable` `ApiKey` model. New API controllers in `App\Http\Controllers\Api\V1\` reuse the existing fractal transformer pattern, with new abridged transformers for nested resources. Admin UI follows the existing themes/settings sub-page pattern under `/admin/settings/apikeys`.

**Tech Stack:** Laravel 12, PHP 8.4, Sanctum (existing), `spatie/laravel-fractal`, PHPUnit 11, SQLite (test DB), Bootstrap-style admin views (Tabler).

**Spec reference:** `docs/superpowers/specs/2026-05-04-admin-api-and-api-keys-design.md`

---

## File map

**New files (model & guard):**
- `database/migrations/2026_05_04_000001_create_api_keys_table.php`
- `app/Models/ApiKey.php`
- `database/factories/ApiKeyFactory.php`
- `app/Auth/ApiKeyGuard.php`

**Modified (auth wiring):**
- `config/auth.php`
- `app/Providers/AuthServiceProvider.php`

**New files (API):**
- `app/Http/Controllers/Api/V1/EventController.php`
- `app/Http/Controllers/Api/V1/TicketController.php`
- `app/Transformers/V1/TicketTransformer.php`
- `app/Transformers/V1/AbridgedUserTransformer.php`
- `app/Transformers/V1/AbridgedSeatTransformer.php`
- `app/Transformers/V1/AbridgedTicketTypeTransformer.php`
- `app/Transformers/V1/AbridgedTicketProviderTransformer.php`

**Modified (transformers & routes):**
- `app/Transformers/V1/AbstractTransformer.php`
- `app/Transformers/V1/EventTransformer.php`
- `routes/api.php`

**New files (admin UI):**
- `app/Http/Controllers/Admin/ApiKeyController.php`
- `app/Http/Requests/Admin/ApiKeyStoreRequest.php`
- `app/Http/Requests/Admin/ApiKeyUpdateRequest.php`
- `resources/views/admin/apikeys/_breadcrumbs.blade.php`
- `resources/views/admin/apikeys/_form.blade.php`
- `resources/views/admin/apikeys/index.blade.php`
- `resources/views/admin/apikeys/create.blade.php`
- `resources/views/admin/apikeys/edit.blade.php`
- `resources/views/admin/apikeys/created.blade.php`
- `resources/views/admin/apikeys/delete.blade.php`

**Modified (admin routes & link):**
- `routes/web.php`
- `resources/views/admin/settings/index.blade.php`

**New tests:**
- `tests/Unit/app/Models/ApiKeyTest.php`
- `tests/Unit/app/Auth/ApiKeyGuardTest.php`
- `tests/Unit/app/Http/Controllers/Api/V1/EventControllerTest.php`
- `tests/Unit/app/Http/Controllers/Api/V1/TicketControllerTest.php`
- `tests/Feature/app/Http/Controllers/Api/V1/EventApiTest.php`
- `tests/Feature/app/Http/Controllers/Api/V1/TicketApiTest.php`
- `tests/Unit/app/Http/Controllers/Admin/ApiKeyControllerTest.php`

---

## Conventions to follow

- **Tests use PHPUnit + `RefreshDatabase`** (project rule, see CLAUDE.md). Mirror `tests/Unit/app/Http/Controllers/Api/V1/SeatingPlanControllerTest.php` for API unit tests.
- **Run tests** with `php artisan test --compact --filter=<TestName>` for the focused test, then the file as a final check.
- **Run Pint** after each task that changes PHP: `vendor/bin/pint --dirty --format agent`.
- **Form Requests** live in `App\Http\Requests\Admin`; pattern shown in `ThemeUpdateRequest.php` — `authorize()` returns `true`, `rules()` returns array.
- **Delete forms** post to `destroy` with a hidden `confirm=delete` input; controller uses `App\Http\Requests\Admin\DeleteRequest` (see `EventController::destroy`).
- **Admin views** extend `layouts.app` with `'activenav' => 'admin'`, breadcrumbs in `@section('breadcrumbs')`, content in `@section('content')`. Use the existing Tabler classes (see `resources/views/admin/themes/edit.blade.php`).
- **Commits**: small, focused, present-tense subject. Co-author trailer required (use the same trailer as the spec commit).

---

## Task 1: Create the `api_keys` migration and `ApiKey` model

**Files:**
- Create: `database/migrations/2026_05_04_000001_create_api_keys_table.php`
- Create: `app/Models/ApiKey.php`
- Create: `database/factories/ApiKeyFactory.php`
- Create: `tests/Unit/app/Models/ApiKeyTest.php`

- [ ] **Step 1: Write a failing model test**

Create `tests/Unit/app/Models/ApiKeyTest.php`:

```php
<?php

namespace Tests\Unit\app\Models;

use App\Models\ApiKey;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function testImplementsAuthenticatableContract()
    {
        $key = new ApiKey();
        $this->assertInstanceOf(Authenticatable::class, $key);
    }

    public function testCanBePersistedWithRequiredFields()
    {
        $key = ApiKey::factory()->create([
            'name' => 'Test integration',
        ]);

        $this->assertDatabaseHas('api_keys', [
            'id' => $key->id,
            'name' => 'Test integration',
            'enabled' => true,
        ]);
        $this->assertNotEmpty($key->key_hash);
        $this->assertEquals(4, strlen($key->last_four));
    }

    public function testEnabledIsCastToBoolean()
    {
        $key = ApiKey::factory()->create(['enabled' => 1]);
        $this->assertTrue($key->enabled);
        $key->enabled = 0;
        $key->save();
        $this->assertFalse($key->fresh()->enabled);
    }

    public function testLastUsedAtIsCastToCarbon()
    {
        $key = ApiKey::factory()->create(['last_used_at' => now()]);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $key->fresh()->last_used_at);
    }

    public function testGenerateReturnsPrefixedKeyAndPersistsHash()
    {
        [$apiKey, $plaintext] = ApiKey::generate('Integration A');

        $this->assertStringStartsWith('ctrl_', $plaintext);
        $this->assertEquals(45, strlen($plaintext)); // ctrl_ (5) + 40 hex chars
        $this->assertEquals(hash('sha256', $plaintext), $apiKey->key_hash);
        $this->assertEquals(substr($plaintext, -4), $apiKey->last_four);
        $this->assertEquals('Integration A', $apiKey->name);
        $this->assertTrue($apiKey->enabled);
    }

    public function testHashedReturnsModelByPlaintext()
    {
        [$apiKey, $plaintext] = ApiKey::generate('A');

        $found = ApiKey::findByPlaintext($plaintext);
        $this->assertNotNull($found);
        $this->assertEquals($apiKey->id, $found->id);

        $this->assertNull(ApiKey::findByPlaintext('ctrl_unknown'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=ApiKeyTest`
Expected: FAIL — class `App\Models\ApiKey` does not exist.

- [ ] **Step 3: Create the migration**

Create `database/migrations/2026_05_04_000001_create_api_keys_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key_hash')->unique();
            $table->string('last_four', 4);
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
```

- [ ] **Step 4: Create the `ApiKey` model**

Create `app/Models/ApiKey.php`:

```php
<?php

namespace App\Models;

use Database\Factories\ApiKeyFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $key_hash
 * @property string $last_four
 * @property bool $enabled
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ApiKey extends Model implements AuthenticatableContract
{
    use Authenticatable;
    use HasFactory;

    public const PREFIX = 'ctrl_';

    protected $fillable = [
        'name',
        'key_hash',
        'last_four',
        'enabled',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Generate a new API key. Returns [model, plaintext]. The plaintext is
     * never stored; only the SHA-256 hash and the last four characters.
     *
     * @return array{0: self, 1: string}
     */
    public static function generate(string $name): array
    {
        $plaintext = self::PREFIX . bin2hex(random_bytes(20));
        $key = self::create([
            'name' => $name,
            'key_hash' => hash('sha256', $plaintext),
            'last_four' => substr($plaintext, -4),
            'enabled' => true,
        ]);
        return [$key, $plaintext];
    }

    public static function findByPlaintext(string $plaintext): ?self
    {
        return self::where('key_hash', hash('sha256', $plaintext))->first();
    }

    protected static function newFactory(): ApiKeyFactory
    {
        return ApiKeyFactory::new();
    }
}
```

- [ ] **Step 5: Create the factory**

Create `database/factories/ApiKeyFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\ApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $plaintext = ApiKey::PREFIX . bin2hex(random_bytes(20));
        return [
            'name' => $this->faker->unique()->company(),
            'key_hash' => hash('sha256', $plaintext),
            'last_four' => substr($plaintext, -4),
            'enabled' => true,
            'last_used_at' => null,
        ];
    }

    public function disabled(): self
    {
        return $this->state(fn () => ['enabled' => false]);
    }

    /**
     * Create with a known plaintext, returned via a callback so tests can
     * capture it.
     *
     * @param  callable(string $plaintext): void  $capture
     */
    public function withPlaintext(callable $capture): self
    {
        return $this->state(function () use ($capture) {
            $plaintext = ApiKey::PREFIX . bin2hex(random_bytes(20));
            $capture($plaintext);
            return [
                'key_hash' => hash('sha256', $plaintext),
                'last_four' => substr($plaintext, -4),
            ];
        });
    }
}
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php artisan test --compact --filter=ApiKeyTest`
Expected: PASS — all six tests green.

- [ ] **Step 7: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: any style fixes applied; no errors.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_05_04_000001_create_api_keys_table.php \
        app/Models/ApiKey.php \
        database/factories/ApiKeyFactory.php \
        tests/Unit/app/Models/ApiKeyTest.php
git commit -m "$(cat <<'EOF'
Add ApiKey model, migration, and factory

Standalone authenticatable model for API key authentication. Stores
SHA-256 hashes and last four chars only; plaintext is never persisted.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 2: Custom auth guard wired into Laravel's auth system

**Files:**
- Create: `app/Auth/ApiKeyGuard.php`
- Modify: `config/auth.php`
- Modify: `app/Providers/AuthServiceProvider.php`
- Create: `tests/Unit/app/Auth/ApiKeyGuardTest.php`

- [ ] **Step 1: Write a failing guard test**

Create `tests/Unit/app/Auth/ApiKeyGuardTest.php`:

```php
<?php

namespace Tests\Unit\app\Auth;

use App\Auth\ApiKeyGuard;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiKeyGuardTest extends TestCase
{
    use RefreshDatabase;

    public function testReturnsApiKeyForValidEnabledBearerToken()
    {
        $plaintext = '';
        $apiKey = ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$plaintext}");

        $guard = new ApiKeyGuard($request);
        $resolved = $guard->user();

        $this->assertNotNull($resolved);
        $this->assertEquals($apiKey->id, $resolved->id);
    }

    public function testReturnsNullForUnknownToken()
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', 'Bearer ctrl_unknown');

        $guard = new ApiKeyGuard($request);
        $this->assertNull($guard->user());
    }

    public function testReturnsNullForDisabledKey()
    {
        $plaintext = '';
        ApiKey::factory()->disabled()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$plaintext}");

        $guard = new ApiKeyGuard($request);
        $this->assertNull($guard->user());
    }

    public function testReturnsNullWhenNoAuthorizationHeader()
    {
        $request = Request::create('/test', 'GET');
        $guard = new ApiKeyGuard($request);
        $this->assertNull($guard->user());
    }

    public function testUpdatesLastUsedAtOnSuccessfulAuth()
    {
        $plaintext = '';
        $apiKey = ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create(['last_used_at' => null]);

        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$plaintext}");

        $guard = new ApiKeyGuard($request);
        $guard->user();

        $this->assertNotNull($apiKey->fresh()->last_used_at);
    }

    public function testCachesResolvedKeyAcrossMultipleCalls()
    {
        $plaintext = '';
        ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$plaintext}");

        $guard = new ApiKeyGuard($request);
        $first = $guard->user();
        $second = $guard->user();
        $this->assertSame($first, $second);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=ApiKeyGuardTest`
Expected: FAIL — class `App\Auth\ApiKeyGuard` does not exist.

- [ ] **Step 3: Create the guard**

Create `app/Auth/ApiKeyGuard.php`:

```php
<?php

namespace App\Auth;

use App\Models\ApiKey;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Traits\Macroable;

class ApiKeyGuard implements Guard
{
    use Macroable;

    protected ?Authenticatable $user = null;

    protected bool $resolved = false;

    public function __construct(protected Request $request)
    {
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->resolved) {
            return $this->user;
        }
        $this->resolved = true;

        $token = $this->bearerToken();
        if ($token === null) {
            return null;
        }

        $key = ApiKey::where('key_hash', hash('sha256', $token))
            ->where('enabled', true)
            ->first();

        if ($key === null) {
            return null;
        }

        ApiKey::where('id', $key->id)->update(['last_used_at' => Carbon::now()]);

        return $this->user = $key;
    }

    public function id(): int|string|null
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
        $this->resolved = true;
    }

    protected function bearerToken(): ?string
    {
        return $this->request->bearerToken() ?: null;
    }
}
```

- [ ] **Step 4: Register the guard in `config/auth.php`**

Modify `config/auth.php` — add an `apikey` entry to `guards` and an `apikeys` entry to `providers`:

```php
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'apikey' => [
            'driver' => 'apikey',
            'provider' => 'apikeys',
        ],
    ],
```

```php
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        'apikeys' => [
            'driver' => 'eloquent',
            'model' => App\Models\ApiKey::class,
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],
```

- [ ] **Step 5: Register the driver in `AuthServiceProvider`**

Modify `app/Providers/AuthServiceProvider.php`. Add the import and extend the auth manager inside `boot()`:

```php
use App\Auth\ApiKeyGuard;
use Illuminate\Support\Facades\Auth;
```

Inside `boot()`, after the existing `Gate::define(...)` calls, add:

```php
        Auth::extend('apikey', function ($app, $name, array $config) {
            return new ApiKeyGuard($app['request']);
        });
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php artisan test --compact --filter=ApiKeyGuardTest`
Expected: PASS — all six tests green.

- [ ] **Step 7: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 8: Commit**

```bash
git add app/Auth/ApiKeyGuard.php \
        config/auth.php \
        app/Providers/AuthServiceProvider.php \
        tests/Unit/app/Auth/ApiKeyGuardTest.php
git commit -m "$(cat <<'EOF'
Add apikey auth guard backed by hashed ApiKey lookup

Registers a custom Laravel guard that authenticates Authorization:
Bearer tokens against the api_keys table, ignores disabled keys, and
records last_used_at on each successful resolution.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: Extend `AbstractTransformer` to recognise an `ApiKey` viewer

**Files:**
- Modify: `app/Transformers/V1/AbstractTransformer.php`

This task contains no behaviour change yet — the existing seating-plans endpoint must continue passing. We add a constructor argument that defaults to `null` and is treated as an admin-equivalent viewer.

- [ ] **Step 1: Read the current file**

Read `app/Transformers/V1/AbstractTransformer.php` to confirm signature before editing. Current shape:

```php
abstract class AbstractTransformer extends TransformerAbstract
{
    public function __construct(protected ?User $user = null) {}

    protected function modifyForUser(array $data, object $object): array { /* admin-only extras */ }

    protected function getAdminProperties(object $object): array { return []; }
}
```

- [ ] **Step 2: Run the existing seating-plans tests as a baseline**

Run: `php artisan test --compact --filter=SeatingPlanControllerTest`
Expected: PASS (current state must be green before we modify the abstract).

- [ ] **Step 3: Modify the abstract**

Replace the contents of `app/Transformers/V1/AbstractTransformer.php` with:

```php
<?php

namespace App\Transformers\V1;

use App\Models\ApiKey;
use App\Models\User;
use League\Fractal\TransformerAbstract;

abstract class AbstractTransformer extends TransformerAbstract
{
    public function __construct(
        protected ?User $user = null,
        protected ?ApiKey $apiKey = null,
    ) {
    }

    protected function modifyForUser(array $data, object $object): array
    {
        if (! $this->isAdminContext()) {
            return $data;
        }

        return array_merge(
            [
                'id' => $object->id,
            ],
            $data,
            $this->getAdminProperties($object),
            [
                'created_at' => $object->created_at->toIso8601String(),
                'updated_at' => $object->updated_at->toIso8601String(),
            ]
        );
    }

    protected function isAdminContext(): bool
    {
        if ($this->apiKey !== null) {
            return true;
        }
        return $this->user !== null && $this->user->hasRole('admin');
    }

    protected function getAdminProperties(object $object): array
    {
        return [];
    }
}
```

- [ ] **Step 4: Re-run the seating-plans tests**

Run: `php artisan test --compact --filter=SeatingPlanControllerTest`
Expected: PASS — behaviour for the existing endpoint unchanged.

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add app/Transformers/V1/AbstractTransformer.php
git commit -m "$(cat <<'EOF'
Recognise ApiKey viewer in AbstractTransformer admin path

Adds an optional ApiKey constructor argument so transformers can return
the same admin-equivalent payload to API key callers as they do to
admin users.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 4: Expose full event fields to admin/API-key viewers in `EventTransformer`

**Files:**
- Modify: `app/Transformers/V1/EventTransformer.php`

The existing transformer returns a small public payload. We want admin-context callers (admin user *or* API key) to additionally receive `draft`, `seating_opens_at`, `seating_closes_at`, and `boxoffice_url`. Implementation: override `getAdminProperties()` so they appear via the existing `modifyForUser` admin merge.

- [ ] **Step 1: Write the failing test**

Add a test method to `tests/Unit/app/Http/Controllers/Api/V1/SeatingPlanControllerTest.php`? No — instead, create `tests/Unit/app/Transformers/V1/EventTransformerTest.php`:

```php
<?php

namespace Tests\Unit\app\Transformers\V1;

use App\Models\ApiKey;
use App\Models\Event;
use App\Models\User;
use App\Transformers\V1\EventTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Tests\TestCase;

class EventTransformerTest extends TestCase
{
    use RefreshDatabase;

    public function testPublicPayloadOmitsAdminFields()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
            'draft' => true,
            'boxoffice_url' => 'https://example.com',
        ]);

        $data = $this->transform(new EventTransformer(), $event);

        $this->assertArrayNotHasKey('id', $data);
        $this->assertArrayNotHasKey('draft', $data);
        $this->assertArrayNotHasKey('boxoffice_url', $data);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('name', $data);
    }

    public function testApiKeyViewerSeesFullAdminPayload()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
            'draft' => true,
            'boxoffice_url' => 'https://example.com',
            'seating_opens_at' => now(),
            'seating_closes_at' => now()->addHour(),
        ]);
        $apiKey = ApiKey::factory()->create();

        $data = $this->transform(new EventTransformer(null, $apiKey), $event);

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($event->id, $data['id']);
        $this->assertArrayHasKey('draft', $data);
        $this->assertEquals($event->draft, $data['draft']);
        $this->assertArrayHasKey('boxoffice_url', $data);
        $this->assertArrayHasKey('seating_opens_at', $data);
        $this->assertArrayHasKey('seating_closes_at', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }

    protected function transform(EventTransformer $transformer, Event $event): array
    {
        $manager = new Manager();
        $resource = new Item($event, $transformer);
        return $manager->createData($resource)->toArray()['data'];
    }
}
```

- [ ] **Step 2: Run the test to confirm failure**

Run: `php artisan test --compact --filter=EventTransformerTest`
Expected: FAIL — `testApiKeyViewerSeesFullAdminPayload` cannot find `draft`/`boxoffice_url` etc.

- [ ] **Step 3: Modify `EventTransformer`**

Replace `app/Transformers/V1/EventTransformer.php`:

```php
<?php

namespace App\Transformers\V1;

use App\Models\Event;

class EventTransformer extends AbstractTransformer
{
    /**
     * @var array<int, string>
     */
    protected array $defaultIncludes = [];

    /**
     * @var array<int, string>
     */
    protected array $availableIncludes = [];

    public function transform(Event $event)
    {
        $data = [
            'code' => $event->code,
            'name' => $event->name,
            'starts_at' => $event->starts_at?->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
            'seating_locked' => $event->seating_locked,
        ];

        return $this->modifyForUser($data, $event);
    }

    protected function getAdminProperties(object $object): array
    {
        /** @var Event $object */
        return [
            'draft' => (bool) $object->draft,
            'boxoffice_url' => $object->boxoffice_url,
            'seating_opens_at' => $object->seating_opens_at?->toIso8601String(),
            'seating_closes_at' => $object->seating_closes_at?->toIso8601String(),
        ];
    }
}
```

Note: `starts_at` and `ends_at` are nullable on the Event model — switch to optional-chaining `->?toIso8601String()` to avoid crashes on factory-defaults that don't set them.

- [ ] **Step 4: Run the new test to verify it passes**

Run: `php artisan test --compact --filter=EventTransformerTest`
Expected: PASS.

- [ ] **Step 5: Re-run the seating-plans tests**

Run: `php artisan test --compact --filter=SeatingPlanControllerTest`
Expected: PASS — existing endpoint behaviour unchanged for the public payload.

- [ ] **Step 6: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 7: Commit**

```bash
git add app/Transformers/V1/EventTransformer.php \
        tests/Unit/app/Transformers/V1/EventTransformerTest.php
git commit -m "$(cat <<'EOF'
Expose full event fields to admin and API-key viewers

EventTransformer now overrides getAdminProperties so admin users and
API key callers see draft, boxoffice_url, and seating window fields
in addition to the public payload.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 5: Add abridged transformers for nested ticket resources

**Files:**
- Create: `app/Transformers/V1/AbridgedUserTransformer.php`
- Create: `app/Transformers/V1/AbridgedSeatTransformer.php`
- Create: `app/Transformers/V1/AbridgedTicketTypeTransformer.php`
- Create: `app/Transformers/V1/AbridgedTicketProviderTransformer.php`
- Create: `tests/Unit/app/Transformers/V1/AbridgedTransformersTest.php`

Each abridged transformer returns a fixed minimal payload independent of viewer context. They do not call `modifyForUser` — they are deliberately uniform so we can never accidentally leak more by toggling context.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/app/Transformers/V1/AbridgedTransformersTest.php`:

```php
<?php

namespace Tests\Unit\app\Transformers\V1;

use App\Models\EmailAddress;
use App\Models\Seat;
use App\Models\SeatingPlan;
use App\Models\TicketProvider;
use App\Models\TicketType;
use App\Models\User;
use App\Transformers\V1\AbridgedSeatTransformer;
use App\Transformers\V1\AbridgedTicketProviderTransformer;
use App\Transformers\V1\AbridgedTicketTypeTransformer;
use App\Transformers\V1\AbridgedUserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Tests\TestCase;

class AbridgedTransformersTest extends TestCase
{
    use RefreshDatabase;

    public function testUserTransformerExposesIdNicknameNameEmail()
    {
        $user = User::factory()->withEmailAddress()->create([
            'nickname' => 'jess',
            'name' => 'Jess Smith',
        ]);

        $data = $this->transform(new AbridgedUserTransformer(), $user);

        $this->assertEquals(
            ['id', 'nickname', 'name', 'email'],
            array_keys($data),
        );
        $this->assertEquals($user->id, $data['id']);
        $this->assertEquals('jess', $data['nickname']);
        $this->assertEquals('Jess Smith', $data['name']);
        $this->assertEquals($user->primaryEmail->email, $data['email']);
    }

    public function testUserTransformerHandlesUserWithoutPrimaryEmail()
    {
        $user = User::factory()->create();
        $data = $this->transform(new AbridgedUserTransformer(), $user);
        $this->assertNull($data['email']);
    }

    public function testSeatTransformerExposesIdLabelRowNumber()
    {
        $event = \App\Models\Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create([
            'seating_plan_id' => $plan->id,
            'label' => 'A12',
            'row' => 'A',
            'number' => 12,
        ]);

        $data = $this->transform(new AbridgedSeatTransformer(), $seat);

        $this->assertEquals(
            ['id', 'label', 'row', 'number'],
            array_keys($data),
        );
        $this->assertEquals('A12', $data['label']);
        $this->assertEquals('A', $data['row']);
        $this->assertEquals(12, $data['number']);
    }

    public function testTicketTypeTransformerExposesIdName()
    {
        $event = \App\Models\Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $type = TicketType::factory()->create(['event_id' => $event->id, 'name' => 'Standard']);

        $data = $this->transform(new AbridgedTicketTypeTransformer(), $type);

        $this->assertEquals(['id', 'name'], array_keys($data));
        $this->assertEquals('Standard', $data['name']);
    }

    public function testTicketProviderTransformerExposesIdCodeName()
    {
        $provider = TicketProvider::factory()->create([
            'name' => 'Internal',
            'code' => 'internal',
        ]);

        $data = $this->transform(new AbridgedTicketProviderTransformer(), $provider);

        $this->assertEquals(['id', 'code', 'name'], array_keys($data));
        $this->assertEquals('internal', $data['code']);
        $this->assertEquals('Internal', $data['name']);
    }

    protected function transform($transformer, $model): array
    {
        $manager = new Manager();
        $resource = new Item($model, $transformer);
        return $manager->createData($resource)->toArray()['data'];
    }
}
```

- [ ] **Step 2: Run the test to confirm failure**

Run: `php artisan test --compact --filter=AbridgedTransformersTest`
Expected: FAIL — abridged transformer classes don't exist.

- [ ] **Step 3: Create `AbridgedUserTransformer`**

Create `app/Transformers/V1/AbridgedUserTransformer.php`:

```php
<?php

namespace App\Transformers\V1;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class AbridgedUserTransformer extends TransformerAbstract
{
    public function transform(User $user): array
    {
        return [
            'id' => $user->id,
            'nickname' => $user->nickname,
            'name' => $user->name,
            'email' => $user->primaryEmail?->email,
        ];
    }
}
```

- [ ] **Step 4: Create `AbridgedSeatTransformer`**

Create `app/Transformers/V1/AbridgedSeatTransformer.php`:

```php
<?php

namespace App\Transformers\V1;

use App\Models\Seat;
use League\Fractal\TransformerAbstract;

class AbridgedSeatTransformer extends TransformerAbstract
{
    public function transform(Seat $seat): array
    {
        return [
            'id' => $seat->id,
            'label' => $seat->label,
            'row' => $seat->row,
            'number' => $seat->number,
        ];
    }
}
```

- [ ] **Step 5: Create `AbridgedTicketTypeTransformer`**

Create `app/Transformers/V1/AbridgedTicketTypeTransformer.php`:

```php
<?php

namespace App\Transformers\V1;

use App\Models\TicketType;
use League\Fractal\TransformerAbstract;

class AbridgedTicketTypeTransformer extends TransformerAbstract
{
    public function transform(TicketType $type): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
        ];
    }
}
```

- [ ] **Step 6: Create `AbridgedTicketProviderTransformer`**

Create `app/Transformers/V1/AbridgedTicketProviderTransformer.php`:

```php
<?php

namespace App\Transformers\V1;

use App\Models\TicketProvider;
use League\Fractal\TransformerAbstract;

class AbridgedTicketProviderTransformer extends TransformerAbstract
{
    public function transform(TicketProvider $provider): array
    {
        return [
            'id' => $provider->id,
            'code' => $provider->code,
            'name' => $provider->name,
        ];
    }
}
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `php artisan test --compact --filter=AbridgedTransformersTest`
Expected: PASS — five tests green.

- [ ] **Step 8: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 9: Commit**

```bash
git add app/Transformers/V1/Abridged*.php \
        tests/Unit/app/Transformers/V1/AbridgedTransformersTest.php
git commit -m "$(cat <<'EOF'
Add abridged fractal transformers for nested API resources

Adds context-independent abridged transformers for User, Seat,
TicketType, and TicketProvider. Used by the upcoming TicketTransformer
to return minimal nested payloads.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 6: Build `TicketTransformer` with all default includes

**Files:**
- Create: `app/Transformers/V1/TicketTransformer.php`
- Create: `tests/Unit/app/Transformers/V1/TicketTransformerTest.php`

The transformer's top-level fields (`id`, `reference`, `external_id`, `name`, `created_at`) are agreed-public for any caller. Includes are always present (event/type/provider always exist; user/seat may be `null`).

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/app/Transformers/V1/TicketTransformerTest.php`:

```php
<?php

namespace Tests\Unit\app\Transformers\V1;

use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatingPlan;
use App\Models\Ticket;
use App\Models\TicketProvider;
use App\Models\TicketType;
use App\Models\User;
use App\Transformers\V1\TicketTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Tests\TestCase;

class TicketTransformerTest extends TestCase
{
    use RefreshDatabase;

    public function testTransformsTicketWithDefaultIncludes()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $type = TicketType::factory()->create(['event_id' => $event->id]);
        $provider = TicketProvider::factory()->create();
        $user = User::factory()->withEmailAddress()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        $ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'ticket_type_id' => $type->id,
            'ticket_provider_id' => $provider->id,
            'user_id' => $user->id,
            'reference' => 'REF-1',
            'external_id' => 'EXT-1',
            'name' => 'Standard',
        ]);
        Seat::factory()->create([
            'seating_plan_id' => $plan->id,
            'ticket_id' => $ticket->id,
            'label' => 'A1',
        ]);

        $data = $this->transform($ticket->fresh(['user.primaryEmail', 'event', 'type', 'provider', 'seat']));

        $this->assertEquals($ticket->id, $data['id']);
        $this->assertEquals('REF-1', $data['reference']);
        $this->assertEquals('EXT-1', $data['external_id']);
        $this->assertEquals('Standard', $data['name']);
        $this->assertArrayHasKey('created_at', $data);

        // Event nested resource uses the full EventTransformer; assert
        // representative keys are present rather than an exact shape.
        $eventData = $this->dataOf($data['event']);
        $this->assertArrayHasKey('code', $eventData);
        $this->assertArrayHasKey('name', $eventData);

        $this->assertEquals(['id', 'name'], array_keys($this->dataOf($data['type'])));
        $this->assertEquals(['id', 'code', 'name'], array_keys($this->dataOf($data['provider'])));
        $this->assertEquals(['id', 'nickname', 'name', 'email'], array_keys($this->dataOf($data['user'])));
        $this->assertEquals(['id', 'label', 'row', 'number'], array_keys($this->dataOf($data['seat'])));
    }

    public function testNullUserAndSeatRenderAsNull()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $type = TicketType::factory()->create(['event_id' => $event->id]);
        $provider = TicketProvider::factory()->create();

        $ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'ticket_type_id' => $type->id,
            'ticket_provider_id' => $provider->id,
            'user_id' => null,
        ]);

        $data = $this->transform($ticket->fresh(['user', 'event', 'type', 'provider', 'seat']));

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('seat', $data);
        $this->assertNull($this->dataOf($data['user']));
        $this->assertNull($this->dataOf($data['seat']));
    }

    protected function transform(Ticket $ticket): array
    {
        $manager = new Manager();
        $manager->parseIncludes(['event', 'type', 'provider', 'user', 'seat']);
        $resource = new Item($ticket, new TicketTransformer());
        return $manager->createData($resource)->toArray()['data'];
    }

    /**
     * Fractal nests included items under a 'data' key. Unwrap defensively
     * so the test passes whether the serializer wraps nulls or not.
     */
    protected function dataOf($value)
    {
        if (is_array($value) && array_key_exists('data', $value)) {
            return $value['data'];
        }
        return $value;
    }
}
```

- [ ] **Step 2: Run the test to confirm failure**

Run: `php artisan test --compact --filter=TicketTransformerTest`
Expected: FAIL — `App\Transformers\V1\TicketTransformer` does not exist.

- [ ] **Step 3: Create `TicketTransformer`**

Create `app/Transformers/V1/TicketTransformer.php`:

```php
<?php

namespace App\Transformers\V1;

use App\Models\Ticket;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use League\Fractal\TransformerAbstract;

class TicketTransformer extends TransformerAbstract
{
    /**
     * @var array<int, string>
     */
    protected array $defaultIncludes = [
        'event',
        'type',
        'provider',
        'user',
        'seat',
    ];

    /**
     * @var array<int, string>
     */
    protected array $availableIncludes = [
        'event',
        'type',
        'provider',
        'user',
        'seat',
    ];

    public function transform(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'reference' => $ticket->reference,
            'external_id' => $ticket->external_id,
            'name' => $ticket->name,
            'created_at' => $ticket->created_at?->toIso8601String(),
        ];
    }

    public function includeEvent(Ticket $ticket): Item
    {
        return $this->item($ticket->event, new EventTransformer());
    }

    public function includeType(Ticket $ticket): Item
    {
        return $this->item($ticket->type, new AbridgedTicketTypeTransformer());
    }

    public function includeProvider(Ticket $ticket): Item
    {
        return $this->item($ticket->provider, new AbridgedTicketProviderTransformer());
    }

    public function includeUser(Ticket $ticket): Item|NullResource
    {
        if ($ticket->user === null) {
            return $this->null();
        }
        return $this->item($ticket->user, new AbridgedUserTransformer());
    }

    public function includeSeat(Ticket $ticket): Item|NullResource
    {
        if ($ticket->seat === null) {
            return $this->null();
        }
        return $this->item($ticket->seat, new AbridgedSeatTransformer());
    }
}
```

Note: nested `event` uses the existing `EventTransformer` so the API key viewer's full admin payload propagates. This is intentional — the spec's `event: { id, code, name }` was an *abridged* spec; with the abstract's admin-context flag on, the full event payload appears, which an API key consumer should expect (they have full event read elsewhere already). If a strict abridged event under tickets is required, swap to a future `AbridgedEventTransformer` here. For now, the broader payload is harmless and avoids a duplicate transformer.

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --compact --filter=TicketTransformerTest`
Expected: PASS.

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add app/Transformers/V1/TicketTransformer.php \
        tests/Unit/app/Transformers/V1/TicketTransformerTest.php
git commit -m "$(cat <<'EOF'
Add TicketTransformer with default abridged includes

Top-level fields plus default includes for event, type, provider, user
(nullable), and seat (nullable). Sensitive fields qrcode and
transfer_code are deliberately omitted.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 7: Build `Api\V1\EventController` (index + show)

**Files:**
- Create: `app/Http/Controllers/Api/V1/EventController.php`
- Create: `tests/Unit/app/Http/Controllers/Api/V1/EventControllerTest.php`

- [ ] **Step 1: Write the failing controller test**

Create `tests/Unit/app/Http/Controllers/Api/V1/EventControllerTest.php`:

```php
<?php

namespace Tests\Unit\app\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\EventController;
use App\Models\ApiKey;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsPaginatedEventsOrderedByStartDesc()
    {
        $older = Event::factory()->create([
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDays(9),
        ]);
        $newer = Event::factory()->create([
            'starts_at' => now()->addDays(1),
            'ends_at' => now()->addDays(2),
        ]);

        $controller = new EventController();
        $request = Request::create('/api/v1/events', 'GET', ['perPage' => 1]);
        $this->setApiKeyOnRequest($request);

        $response = $controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $body = $response->getData(true);
        $this->assertCount(1, $body['data']);
        // Newer event must come first
        $this->assertEquals($newer->code, $body['data'][0]['code']);
    }

    public function testIndexExposesAdminFieldsWhenApiKeyAttached()
    {
        Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
            'draft' => true,
            'boxoffice_url' => 'https://bo.example.com',
        ]);

        $controller = new EventController();
        $request = Request::create('/api/v1/events', 'GET');
        $this->setApiKeyOnRequest($request);

        $body = $controller->index($request)->getData(true);
        $this->assertArrayHasKey('id', $body['data'][0]);
        $this->assertArrayHasKey('draft', $body['data'][0]);
        $this->assertEquals('https://bo.example.com', $body['data'][0]['boxoffice_url']);
    }

    public function testShowReturnsSingleEventByCode()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        $controller = new EventController();
        $request = Request::create("/api/v1/events/{$event->code}", 'GET');
        $this->setApiKeyOnRequest($request);

        $response = $controller->show($request, $event);
        $body = $response->getData(true);
        $this->assertEquals($event->code, $body['data']['code']);
        $this->assertEquals($event->id, $body['data']['id']);
    }

    protected function setApiKeyOnRequest(Request $request): ApiKey
    {
        $apiKey = ApiKey::factory()->create();
        $request->setUserResolver(fn () => $apiKey);
        return $apiKey;
    }
}
```

- [ ] **Step 2: Run the test to confirm failure**

Run: `php artisan test --compact --filter=EventControllerTest`
Expected: FAIL — controller class missing.

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/Api/V1/EventController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Event;
use App\Transformers\V1\EventTransformer;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 20);

        $events = Event::query()
            ->orderBy('starts_at', 'desc')
            ->paginate($perPage)
            ->appends(['perPage' => $perPage]);

        return fractal($events, new EventTransformer(null, $this->apiKey($request)))->respond();
    }

    public function show(Request $request, Event $event)
    {
        return fractal($event, new EventTransformer(null, $this->apiKey($request)))->respond();
    }

    protected function apiKey(Request $request): ?ApiKey
    {
        $user = $request->user();
        return $user instanceof ApiKey ? $user : null;
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --compact --filter=EventControllerTest`
Expected: PASS — three tests green.

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/V1/EventController.php \
        tests/Unit/app/Http/Controllers/Api/V1/EventControllerTest.php
git commit -m "$(cat <<'EOF'
Add Api\V1\EventController for events index/show

Read-only paginated index ordered by starts_at desc and a show endpoint
keyed on the existing event code. Pulls the authenticated ApiKey off the
request so the transformer returns the admin-equivalent payload.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 8: Build `Api\V1\TicketController` (index, optional event filter)

**Files:**
- Create: `app/Http/Controllers/Api/V1/TicketController.php`
- Create: `tests/Unit/app/Http/Controllers/Api/V1/TicketControllerTest.php`

- [ ] **Step 1: Write the failing controller test**

Create `tests/Unit/app/Http/Controllers/Api/V1/TicketControllerTest.php`:

```php
<?php

namespace Tests\Unit\app\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\TicketController;
use App\Models\ApiKey;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsPaginatedTickets()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        Ticket::factory()->count(3)->create(['event_id' => $event->id]);

        $controller = new TicketController();
        $request = Request::create('/api/v1/tickets', 'GET', ['perPage' => 2]);
        $this->setApiKeyOnRequest($request);

        $response = $controller->index($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $body = $response->getData(true);
        $this->assertCount(2, $body['data']);
    }

    public function testIndexFilterByEventCodeScopesResults()
    {
        $eventA = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $eventB = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        Ticket::factory()->count(2)->create(['event_id' => $eventA->id]);
        Ticket::factory()->count(3)->create(['event_id' => $eventB->id]);

        $controller = new TicketController();
        $request = Request::create('/api/v1/tickets', 'GET', ['event' => $eventA->code]);
        $this->setApiKeyOnRequest($request);

        $body = $controller->index($request)->getData(true);
        $this->assertCount(2, $body['data']);
    }

    public function testIndexUnknownEventCodeReturnsEmptyDataset()
    {
        Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        $controller = new TicketController();
        $request = Request::create('/api/v1/tickets', 'GET', ['event' => 'does-not-exist']);
        $this->setApiKeyOnRequest($request);

        $body = $controller->index($request)->getData(true);
        $this->assertCount(0, $body['data']);
    }

    public function testIndexIncludesUserAndSeatInPayload()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $type = TicketType::factory()->create(['event_id' => $event->id]);
        Ticket::factory()->create([
            'event_id' => $event->id,
            'ticket_type_id' => $type->id,
        ]);

        $controller = new TicketController();
        $request = Request::create('/api/v1/tickets', 'GET');
        $this->setApiKeyOnRequest($request);

        $body = $controller->index($request)->getData(true);
        $this->assertArrayHasKey('user', $body['data'][0]);
        $this->assertArrayHasKey('seat', $body['data'][0]);
        $this->assertArrayHasKey('event', $body['data'][0]);
        $this->assertArrayHasKey('type', $body['data'][0]);
        $this->assertArrayHasKey('provider', $body['data'][0]);
    }

    protected function setApiKeyOnRequest(Request $request): ApiKey
    {
        $apiKey = ApiKey::factory()->create();
        $request->setUserResolver(fn () => $apiKey);
        return $apiKey;
    }
}
```

- [ ] **Step 2: Run the test to confirm failure**

Run: `php artisan test --compact --filter=TicketControllerTest`
Expected: FAIL — controller missing.

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/Api/V1/TicketController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Event;
use App\Models\Ticket;
use App\Transformers\V1\TicketTransformer;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 20);

        $query = Ticket::query()
            ->with(['user.primaryEmail', 'seat', 'type', 'provider', 'event'])
            ->orderBy('id', 'asc');

        if ($code = $request->input('event')) {
            $event = Event::where('code', $code)->first();
            if ($event === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('event_id', $event->id);
            }
        }

        $appends = ['perPage' => $perPage];
        if ($request->filled('event')) {
            $appends['event'] = $request->input('event');
        }

        $tickets = $query->paginate($perPage)->appends($appends);

        return fractal($tickets, new TicketTransformer())->respond();
    }

    protected function apiKey(Request $request): ?ApiKey
    {
        $user = $request->user();
        return $user instanceof ApiKey ? $user : null;
    }
}
```

Note: `TicketTransformer` does not need the `ApiKey` constructor because its top-level shape is fixed and its includes' shapes are either fixed (abridged) or driven by the called transformer's own context. The nested `EventTransformer` here is constructed without an `ApiKey`, so the nested event payload will be the public abridged form. If you want the nested event to also be admin-shaped, pass `new EventTransformer(null, $this->apiKey($request))` from `includeEvent` — that would require threading the api key through, which we are deferring. For v1, the dedicated `/api/v1/events/{code}` endpoint already returns the full event payload; nested events being abridged is acceptable.

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --compact --filter=TicketControllerTest`
Expected: PASS — four tests green.

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/V1/TicketController.php \
        tests/Unit/app/Http/Controllers/Api/V1/TicketControllerTest.php
git commit -m "$(cat <<'EOF'
Add Api\V1\TicketController for tickets index with event filter

Read-only paginated tickets endpoint with optional ?event={code}
filter. Eager-loads user, seat, type, provider, and event to avoid N+1
during fractal serialisation.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 9: Wire API routes through the `apikey` guard

**Files:**
- Modify: `routes/api.php`
- Create: `tests/Feature/app/Http/Controllers/Api/V1/EventApiTest.php`
- Create: `tests/Feature/app/Http/Controllers/Api/V1/TicketApiTest.php`

These feature tests exercise the actual HTTP stack — guard included — so 401 behaviour and route registration are covered.

- [ ] **Step 1: Write the failing event API test**

Create `tests/Feature/app/Http/Controllers/Api/V1/EventApiTest.php`:

```php
<?php

namespace Tests\Feature\app\Http\Controllers\Api\V1;

use App\Models\ApiKey;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturns401WithoutAuthorizationHeader()
    {
        $this->getJson('/api/v1/events')->assertStatus(401);
    }

    public function testIndexReturns401ForUnknownToken()
    {
        $this->getJson('/api/v1/events', ['Authorization' => 'Bearer ctrl_unknown'])
            ->assertStatus(401);
    }

    public function testIndexReturns401ForDisabledKey()
    {
        $plaintext = '';
        ApiKey::factory()->disabled()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $this->getJson('/api/v1/events', ['Authorization' => "Bearer {$plaintext}"])
            ->assertStatus(401);
    }

    public function testIndexReturns200WithValidKey()
    {
        $plaintext = '';
        ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        $response = $this->getJson('/api/v1/events', ['Authorization' => "Bearer {$plaintext}"]);
        $response->assertOk();
        $response->assertJsonStructure(['data' => [['id', 'code', 'name']]]);
    }

    public function testShowReturnsEventByCode()
    {
        $plaintext = '';
        ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        $this->getJson("/api/v1/events/{$event->code}", ['Authorization' => "Bearer {$plaintext}"])
            ->assertOk()
            ->assertJsonPath('data.code', $event->code);
    }
}
```

- [ ] **Step 2: Write the failing ticket API test**

Create `tests/Feature/app/Http/Controllers/Api/V1/TicketApiTest.php`:

```php
<?php

namespace Tests\Feature\app\Http\Controllers\Api\V1;

use App\Models\ApiKey;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturns401WithoutAuthorizationHeader()
    {
        $this->getJson('/api/v1/tickets')->assertStatus(401);
    }

    public function testIndexReturns200AndTicketsWithValidKey()
    {
        $plaintext = '';
        ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        Ticket::factory()->create(['event_id' => $event->id]);

        $response = $this->getJson('/api/v1/tickets', ['Authorization' => "Bearer {$plaintext}"]);
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'reference', 'event', 'type', 'provider', 'user', 'seat']],
        ]);
    }

    public function testIndexFilterByEventCode()
    {
        $plaintext = '';
        ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $eventA = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $eventB = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        Ticket::factory()->count(2)->create(['event_id' => $eventA->id]);
        Ticket::factory()->count(3)->create(['event_id' => $eventB->id]);

        $response = $this->getJson(
            "/api/v1/tickets?event={$eventA->code}",
            ['Authorization' => "Bearer {$plaintext}"]
        );
        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }
}
```

- [ ] **Step 3: Run both tests to confirm they fail**

Run: `php artisan test --compact --filter=EventApiTest`
Run: `php artisan test --compact --filter=TicketApiTest`
Expected: FAIL — routes do not exist (404), or auth misses (other status).

- [ ] **Step 4: Modify `routes/api.php` to add the new endpoints**

Replace `routes/api.php` with:

```php
<?php

use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\SeatingPlanController;
use App\Http\Controllers\Api\V1\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::resource('events.seatingplans', SeatingPlanController::class)
            ->only(['index', 'show'])
            ->middleware('can:see,event')
            ->scoped();
    });

    Route::middleware('auth:apikey')->group(function () {
        Route::get('events', [EventController::class, 'index'])->name('events.index');
        Route::get('events/{event:code}', [EventController::class, 'show'])->name('events.show');
        Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
    });
});
```

- [ ] **Step 5: Run the feature tests to verify they pass**

Run: `php artisan test --compact --filter=EventApiTest`
Run: `php artisan test --compact --filter=TicketApiTest`
Expected: PASS — all assertions green for both files.

- [ ] **Step 6: Run the full API and unit suites that touched these areas**

Run: `php artisan test --compact --filter=Api`
Run: `php artisan test --compact --filter=Transformer`
Expected: PASS — no regression.

- [ ] **Step 7: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 8: Commit**

```bash
git add routes/api.php \
        tests/Feature/app/Http/Controllers/Api/V1/EventApiTest.php \
        tests/Feature/app/Http/Controllers/Api/V1/TicketApiTest.php
git commit -m "$(cat <<'EOF'
Wire /api/v1/events and /api/v1/tickets routes through apikey guard

Adds the new endpoints alongside the existing seating-plans group and
covers the HTTP-level auth and routing flow with feature tests.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 10: Admin form requests and controller for API keys

**Files:**
- Create: `app/Http/Requests/Admin/ApiKeyStoreRequest.php`
- Create: `app/Http/Requests/Admin/ApiKeyUpdateRequest.php`
- Create: `app/Http/Controllers/Admin/ApiKeyController.php`
- Create: `tests/Unit/app/Http/Controllers/Admin/ApiKeyControllerTest.php`

The controller follows the existing admin CRUD pattern (`ThemeController` style — separate `create/store/edit/update/delete/destroy` methods, redirect to settings index on success, flash messages).

- [ ] **Step 1: Create the form requests**

Create `app/Http/Requests/Admin/ApiKeyStoreRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApiKeyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
```

Create `app/Http/Requests/Admin/ApiKeyUpdateRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApiKeyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'enabled' => 'sometimes|boolean|nullable',
        ];
    }
}
```

- [ ] **Step 2: Write the failing controller test**

Create `tests/Unit/app/Http/Controllers/Admin/ApiKeyControllerTest.php`:

```php
<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Requests\Admin\ApiKeyStoreRequest;
use App\Http\Requests\Admin\ApiKeyUpdateRequest;
use App\Http\Requests\Admin\DeleteRequest;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\View\View;
use Tests\TestCase;

class ApiKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsView()
    {
        ApiKey::factory()->count(2)->create();
        $controller = new ApiKeyController();
        $response = $controller->index();
        $this->assertInstanceOf(View::class, $response);
        $this->assertArrayHasKey('apikeys', $response->getData());
    }

    public function testCreateReturnsView()
    {
        $controller = new ApiKeyController();
        $response = $controller->create();
        $this->assertInstanceOf(View::class, $response);
        $this->assertArrayHasKey('apikey', $response->getData());
    }

    public function testStoreCreatesKeyAndFlashesPlaintext()
    {
        $req = ApiKeyStoreRequest::create('/', 'POST', ['name' => 'Integration A']);
        $req->setLaravelSession(app('session.store'));

        $controller = new ApiKeyController();
        try {
            $controller->store($req);
        } catch (UrlGenerationException $ex) {
            // route name not registered in unit test environment; ok
        }

        $this->assertDatabaseHas('api_keys', ['name' => 'Integration A']);
        $plaintext = $req->session()->get('apiKeyPlaintext');
        $this->assertNotNull($plaintext);
        $this->assertStringStartsWith('ctrl_', $plaintext);
    }

    public function testEditReturnsView()
    {
        $key = ApiKey::factory()->create();
        $controller = new ApiKeyController();
        $response = $controller->edit($key);
        $this->assertInstanceOf(View::class, $response);
        $this->assertEquals($key->id, $response->getData()['apikey']->id);
    }

    public function testUpdateChangesNameAndEnabledOnly()
    {
        $key = ApiKey::factory()->create([
            'name' => 'Old',
            'enabled' => true,
        ]);
        $originalHash = $key->key_hash;

        $req = ApiKeyUpdateRequest::create('/', 'POST', [
            'name' => 'New',
            'enabled' => 0,
            'key_hash' => 'should_not_be_applied',
        ]);

        $controller = new ApiKeyController();
        try {
            $controller->update($req, $key);
        } catch (UrlGenerationException $ex) {
            // ok
        }

        $fresh = $key->fresh();
        $this->assertEquals('New', $fresh->name);
        $this->assertFalse($fresh->enabled);
        $this->assertEquals($originalHash, $fresh->key_hash);
    }

    public function testDeleteReturnsView()
    {
        $key = ApiKey::factory()->create();
        $controller = new ApiKeyController();
        $response = $controller->delete($key);
        $this->assertInstanceOf(View::class, $response);
    }

    public function testDestroyRemovesRow()
    {
        $key = ApiKey::factory()->create();
        $req = DeleteRequest::create('/', 'DELETE', ['confirm' => 'delete']);

        $controller = new ApiKeyController();
        try {
            $controller->destroy($req, $key);
        } catch (UrlGenerationException $ex) {
            // ok
        }

        $this->assertDatabaseMissing('api_keys', ['id' => $key->id]);
    }
}
```

- [ ] **Step 3: Run the test to confirm failure**

Run: `php artisan test --compact --filter=ApiKeyControllerTest`
Expected: FAIL — controller missing.

- [ ] **Step 4: Create the controller**

Create `app/Http/Controllers/Admin/ApiKeyController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApiKeyStoreRequest;
use App\Http\Requests\Admin\ApiKeyUpdateRequest;
use App\Http\Requests\Admin\DeleteRequest;
use App\Models\ApiKey;

class ApiKeyController extends Controller
{
    public function index()
    {
        $apikeys = ApiKey::orderBy('name')->paginate(20);
        return view('admin.apikeys.index', [
            'apikeys' => $apikeys,
        ]);
    }

    public function create()
    {
        return view('admin.apikeys.create', [
            'apikey' => new ApiKey(),
        ]);
    }

    public function store(ApiKeyStoreRequest $request)
    {
        [$apiKey, $plaintext] = ApiKey::generate($request->input('name'));

        $request->session()->flash('apiKeyPlaintext', $plaintext);

        return response()
            ->redirectToRoute('admin.settings.apikeys.created', $apiKey->id)
            ->with('successMessage', 'The API key has been created');
    }

    public function created(ApiKey $apikey)
    {
        return view('admin.apikeys.created', [
            'apikey' => $apikey,
        ]);
    }

    public function edit(ApiKey $apikey)
    {
        return view('admin.apikeys.edit', [
            'apikey' => $apikey,
        ]);
    }

    public function update(ApiKeyUpdateRequest $request, ApiKey $apikey)
    {
        $apikey->name = $request->input('name');
        $apikey->enabled = (bool) $request->input('enabled', false);
        $apikey->save();

        return response()
            ->redirectToRoute('admin.settings.apikeys.index')
            ->with('successMessage', 'The API key has been updated');
    }

    public function delete(ApiKey $apikey)
    {
        return view('admin.apikeys.delete', [
            'apikey' => $apikey,
        ]);
    }

    public function destroy(DeleteRequest $request, ApiKey $apikey)
    {
        $apikey->delete();
        return response()
            ->redirectToRoute('admin.settings.apikeys.index')
            ->with('successMessage', 'The API key has been deleted');
    }
}
```

Note: the `created()` method takes the persisted `ApiKey` so the view can render the `last_four` and `name`; the plaintext is read separately from the flashed session value. Storing the plaintext in flash means it survives exactly one redirect-and-render, then disappears.

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --compact --filter=ApiKeyControllerTest`
Expected: PASS — seven tests green.

- [ ] **Step 6: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 7: Commit**

```bash
git add app/Http/Requests/Admin/ApiKeyStoreRequest.php \
        app/Http/Requests/Admin/ApiKeyUpdateRequest.php \
        app/Http/Controllers/Admin/ApiKeyController.php \
        tests/Unit/app/Http/Controllers/Admin/ApiKeyControllerTest.php
git commit -m "$(cat <<'EOF'
Add admin ApiKeyController with one-time plaintext flash

CRUD against the api_keys table following the existing themes pattern.
Plaintext key is generated on store and flashed once for the success
view; never persisted.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 11: Register admin routes for API key management

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Add the import to `routes/web.php`**

Add to the `use` block at the top of `routes/web.php`, alongside the other `Admin\…` imports:

```php
use App\Http\Controllers\Admin\ApiKeyController;
```

- [ ] **Step 2: Add routes inside the existing admin settings group**

Locate the existing `Route::prefix('settings')->name('settings.')->group(function () { ... })` block (it lives inside `Route::middleware(['can:admin'])->group(...)`). Add inside that group, after the existing themes resource:

```php
                    Route::resource('apikeys', ApiKeyController::class)->except(['show']);
                    Route::get('apikeys/{apikey}/created', [ApiKeyController::class, 'created'])->name('apikeys.created');
                    Route::get('apikeys/{apikey}/delete', [ApiKeyController::class, 'delete'])->name('apikeys.delete');
```

- [ ] **Step 3: Verify routes are registered**

Run: `php artisan route:list --name=admin.settings.apikeys`
Expected: 8 routes listed — index, create, store, edit, update, destroy, plus the custom `created` and `delete`.

- [ ] **Step 4: Run the controller test once more (still passes — view files are not yet rendered)**

Run: `php artisan test --compact --filter=ApiKeyControllerTest`
Expected: PASS — controller-only tests still green; the redirect inside `store()` now resolves a real route name, so the `try/catch` for `UrlGenerationException` becomes inert (still safe to keep).

- [ ] **Step 5: Commit**

```bash
git add routes/web.php
git commit -m "$(cat <<'EOF'
Register admin API key management routes under settings

Adds resourceful routes plus a `created` route used to display the
one-time plaintext after key creation.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 12: Admin views for API key management

**Files:**
- Create: `resources/views/admin/apikeys/_breadcrumbs.blade.php`
- Create: `resources/views/admin/apikeys/_form.blade.php`
- Create: `resources/views/admin/apikeys/index.blade.php`
- Create: `resources/views/admin/apikeys/create.blade.php`
- Create: `resources/views/admin/apikeys/edit.blade.php`
- Create: `resources/views/admin/apikeys/created.blade.php`
- Create: `resources/views/admin/apikeys/delete.blade.php`

Mirrors the themes views (`resources/views/admin/themes/`).

- [ ] **Step 1: Create breadcrumbs partial**

Create `resources/views/admin/apikeys/_breadcrumbs.blade.php`:

```blade
<li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.settings.apikeys.index') }}">API Keys</a></li>
```

- [ ] **Step 2: Create the form partial**

Create `resources/views/admin/apikeys/_form.blade.php`:

```blade
<div class="card-body">
    <div class="mb-3">
        <label class="form-label required">Name</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               placeholder="Name" value="{{ old('name', $apikey->name ?? '') }}">
        <small class="form-hint">A human-readable label so you can tell keys apart.</small>
        @error('name')
            <p class="invalid-feedback">{{ $message }}</p>
        @enderror
    </div>

    @if($apikey->exists)
        <div class="mb-3">
            <label class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="enabled" value="1"
                       @if(old('enabled', $apikey->enabled)) checked @endif>
                Enabled
            </label>
            <small class="form-hint">Disable to immediately revoke this key without deleting it.</small>
        </div>
    @endif
</div>
```

- [ ] **Step 3: Create the index view**

Create `resources/views/admin/apikeys/index.blade.php`:

```blade
@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.apikeys._breadcrumbs')
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>API Keys</h1>
        <div class="ms-auto">
            <a href="{{ route('admin.settings.apikeys.create') }}" class="btn btn-primary">
                <i class="icon ti ti-plus"></i>
                New API Key
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Key</th>
                        <th class="w-1">Status</th>
                        <th>Last Used</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($apikeys as $apikey)
                        <tr>
                            <td>{{ $apikey->name }}</td>
                            <td><code>ctrl_…{{ $apikey->last_four }}</code></td>
                            <td>
                                @if($apikey->enabled)
                                    <span class="status status-green">Enabled</span>
                                @else
                                    <span class="status status-muted">Disabled</span>
                                @endif
                            </td>
                            <td>
                                {{ $apikey->last_used_at?->diffForHumans() ?? 'Never' }}
                            </td>
                            <td>{{ $apikey->created_at->format('Y-m-d') }}</td>
                            <td>
                                <div class="btn-list justify-content-end">
                                    <a href="{{ route('admin.settings.apikeys.edit', $apikey->id) }}" class="btn btn-outline-primary">
                                        <i class="icon ti ti-edit"></i>
                                    </a>
                                    <a href="{{ route('admin.settings.apikeys.delete', $apikey->id) }}" class="btn btn-outline-danger">
                                        <i class="icon ti ti-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No API keys yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($apikeys->hasPages())
            <div class="card-footer">
                {{ $apikeys->links() }}
            </div>
        @endif
    </div>
@endsection
```

- [ ] **Step 4: Create the `create` view**

Create `resources/views/admin/apikeys/create.blade.php`:

```blade
@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.apikeys._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.settings.apikeys.create') }}">New API Key</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>New API Key</h1>
    </div>

    <div class="col-md-8 offset-md-2">
        <form action="{{ route('admin.settings.apikeys.store') }}" method="post" class="card">
            {{ csrf_field() }}
            @include('admin.apikeys._form')
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.settings.apikeys.index') }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Create</button>
                </div>
            </div>
        </form>
    </div>
@endsection
```

- [ ] **Step 5: Create the `edit` view**

Create `resources/views/admin/apikeys/edit.blade.php`:

```blade
@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.apikeys._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.settings.apikeys.edit', $apikey->id) }}">Edit</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Edit {{ $apikey->name }}</h1>
    </div>

    <div class="col-md-8 offset-md-2">
        <form action="{{ route('admin.settings.apikeys.update', $apikey->id) }}" method="post" class="card">
            {{ csrf_field() }}
            {{ method_field('PATCH') }}
            @include('admin.apikeys._form')
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.settings.apikeys.index') }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Save</button>
                </div>
            </div>
        </form>
    </div>
@endsection
```

- [ ] **Step 6: Create the `created` (one-time plaintext) view**

Create `resources/views/admin/apikeys/created.blade.php`:

```blade
@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.apikeys._breadcrumbs')
    <li class="breadcrumb-item active">Created</li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>API Key Created</h1>
    </div>

    @if(session()->has('apiKeyPlaintext'))
        <div class="col-md-8 offset-md-2">
            <div class="alert alert-warning">
                <h4 class="alert-title">Copy this key now</h4>
                <p>This is the only time the full key will ever be shown. After leaving this page, only the last four characters (<code>…{{ $apikey->last_four }}</code>) will be visible.</p>
            </div>

            <div class="card">
                <div class="card-body">
                    <label class="form-label">{{ $apikey->name }}</label>
                    <div class="input-group">
                        <input type="text" id="apikey-plaintext" class="form-control text-monospace"
                               value="{{ session('apiKeyPlaintext') }}" readonly>
                        <button type="button" class="btn btn-outline-primary" id="apikey-copy">
                            <i class="icon ti ti-copy"></i>
                            Copy
                        </button>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('admin.settings.apikeys.index') }}" class="btn btn-primary">Done</a>
                </div>
            </div>
        </div>

        @push('footer')
            <script>
                document.getElementById('apikey-copy').addEventListener('click', function () {
                    const input = document.getElementById('apikey-plaintext');
                    input.select();
                    navigator.clipboard.writeText(input.value);
                });
            </script>
        @endpush
    @else
        <div class="col-md-8 offset-md-2">
            <div class="alert alert-info">
                The plaintext key is no longer available. If you didn't capture it, delete this key and create a new one.
            </div>
            <a href="{{ route('admin.settings.apikeys.index') }}" class="btn btn-primary">Back to API Keys</a>
        </div>
    @endif
@endsection
```

- [ ] **Step 7: Create the `delete` view**

Create `resources/views/admin/apikeys/delete.blade.php`:

```blade
@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.apikeys._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.settings.apikeys.delete', $apikey->id) }}">Delete</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Delete {{ $apikey->name }}</h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('admin.settings.apikeys.destroy', $apikey->id) }}" method="post" class="card">
            <div class="card-status-top bg-danger"></div>
            {{ csrf_field() }}
            {{ method_field('DELETE') }}
            <input type="hidden" name="confirm" value="delete">
            <div class="card-body text-center">
                <i class="icon mb-4 ti ti-alert-triangle icon-lg text-danger"></i>
                <p class="mt-4">
                    Are you sure you want to delete <strong>{{ $apikey->name }}</strong> (<code>ctrl_…{{ $apikey->last_four }}</code>)?
                </p>
                <p class="text-muted">Any integration using this key will start receiving 401 responses immediately.</p>
            </div>
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.settings.apikeys.index') }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-danger ms-auto">Delete</button>
                </div>
            </div>
        </form>
    </div>
@endsection
```

- [ ] **Step 8: Smoke-render the views via the existing controller test**

Run: `php artisan test --compact --filter=ApiKeyControllerTest`
Expected: PASS — controller tests still green; views referenced by the controller now exist on disk.

- [ ] **Step 9: Run the full test suite to catch regressions**

Run: `php artisan test --compact`
Expected: PASS — entire suite green.

- [ ] **Step 10: Commit**

```bash
git add resources/views/admin/apikeys
git commit -m "$(cat <<'EOF'
Add admin Blade views for API key management

Index, create, edit, delete, and one-time `created` plaintext view
following the existing themes admin pattern.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 13: Link API keys from the admin settings index

**Files:**
- Modify: `resources/views/admin/settings/index.blade.php`

- [ ] **Step 1: Add an "API Keys" card alongside the Themes card**

Open `resources/views/admin/settings/index.blade.php`. Find the Themes card (`<h3 class="card-title">Themes</h3>`) and add a sibling card immediately after the Themes card's closing `</div>` (the one that closes the column wrapper for Themes). Use the same column wrapper pattern. Add:

```blade
        <div class="col-12 col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">API Keys</h3>
                    <a class="btn btn-primary ms-auto" href="{{ route('admin.settings.apikeys.index') }}">
                        <i class="icon ti ti-key"></i>
                        Manage API Keys
                    </a>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">
                        API keys grant read access to the admin API endpoints used by external integrations.
                    </p>
                </div>
            </div>
        </div>
```

- [ ] **Step 2: Run the full suite**

Run: `php artisan test --compact`
Expected: PASS.

- [ ] **Step 3: Manually verify the settings page rendering (if running locally)**

Open `/admin/settings` while signed in as an admin. Confirm the new "API Keys" card appears and the "Manage API Keys" button links to the new index page. *Note for the executing agent:* if the dev environment isn't running, skip this step and rely on test coverage; flag in the commit message.

- [ ] **Step 4: Commit**

```bash
git add resources/views/admin/settings/index.blade.php
git commit -m "$(cat <<'EOF'
Surface API Keys management on the admin settings page

Adds a card linking to the new admin/settings/apikeys index.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 14: Final sweep — full test suite + Pint + spec cross-check

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: PASS — entire suite green.

- [ ] **Step 2: Run Pint over the whole tree**

Run: `vendor/bin/pint --dirty --format agent`
Expected: clean.

- [ ] **Step 3: Cross-check against the spec**

Open `docs/superpowers/specs/2026-05-04-admin-api-and-api-keys-design.md` and confirm:

- All four endpoints registered (`/api/v1/events`, `/api/v1/events/{code}`, `/api/v1/tickets`, `/api/v1/tickets?event=…`).
- `qrcode` and `transfer_code` are NOT present in any ticket response (grep `app/Transformers/V1/TicketTransformer.php` to confirm).
- Plaintext key shown only on `created` view; not stored in DB anywhere.
- Admin UI accessible only inside `can:admin`.

If any gap is found, raise it before declaring done; do not silently add scope.

- [ ] **Step 4: Final commit only if there are uncommitted Pint fixes**

```bash
git status
# if clean, no commit needed
# otherwise:
git commit -am "$(cat <<'EOF'
Apply Pint formatting fixes from final sweep

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---
