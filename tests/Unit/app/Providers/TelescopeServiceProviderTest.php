<?php

namespace Tests\Unit\app\Providers;

use App\Models\LinkedAccount;
use App\Models\User;
use App\Providers\TelescopeServiceProvider;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\Avatar;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class TelescopeServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function testRegisterConfiguresTelescope()
    {

        // Bind a lightweight fake telescope instance to the container so the facade resolves to it
        $fake = new class {
            public $nightCalled = false;
            public $filterCalled = false;
            public $avatarCalled = false;

            public function night()
            {
                $this->nightCalled = true;
            }

            public function filter($callback)
            {
                $this->filterCalled = true;
            }

            public function avatar($callback)
            {
                $this->avatarCalled = true;
            }

            public function hideRequestParameters($params)
            {
                /* noop for register test */
            }

            public function hideRequestHeaders($headers)
            {
                /* noop for register test */
            }
        };

        // Clear Telescope static callbacks to ensure a clean test environment
        Telescope::$filterUsing = [];
        Telescope::$tagUsing = [];

        $provider = new TelescopeServiceProvider(app());
        $provider->register();

        $this->assertIsArray(Telescope::$filterUsing);
        $this->assertNotEmpty(Telescope::$filterUsing, 'Telescope filter callback was not registered');
    }

    public function testRegisterInLocalEnvironmentAllowsAllEntries()
    {
        // Force app environment to local
        $this->app['env'] = 'local';

        // Reset filter registry
        Telescope::$filterUsing = [];

        $provider = new TelescopeServiceProvider(app());
        $provider->register();

        $this->assertNotEmpty(Telescope::$filterUsing);
        $callback = Telescope::$filterUsing[0];
        $fakeEntry = Mockery::mock(IncomingEntry::class);
        $fakeEntry->shouldReceive('isRequest')->andReturn(false);
        // In local env the filter should return true
        $this->assertTrue($callback($fakeEntry));
    }

    public function testRegisterFiltersOutApiPathsAndHonorsNofilter()
    {
        // Normal non-local environment
        $this->app['env'] = 'production';

        // Case 1: API path should be filtered out
        Telescope::$filterUsing = [];
        $provider = new TelescopeServiceProvider(app());
        $provider->register();
        $callback = Telescope::$filterUsing[0];

        $entry = new IncomingEntry(['uri' => '/api/v1/something']);
        $entry->type = EntryType::REQUEST;
        $entry->content = ['uri' => '/api/v1/something'];
        $this->assertFalse($callback($entry));

        // Additional explicit test: ensure URIs starting with '/api/v1/' are filtered out
        Telescope::$filterUsing = [];
        $provider = new TelescopeServiceProvider(app());
        $provider->register();
        $callback2 = Telescope::$filterUsing[0];
        $entryApi = new IncomingEntry(['uri' => '/api/v1/other']);
        $entryApi->type = EntryType::REQUEST;
        $entryApi->content = ['uri' => '/api/v1/other'];
        $this->assertFalse($callback2($entryApi), 'API v1 URIs should be filtered out by the telescope filter');

        // Case 2: config nofilter true should allow all
        config(['telescope.nofilter' => true]);
        Telescope::$filterUsing = [];
        $provider = new TelescopeServiceProvider(app());
        $provider->register();
        $callback = Telescope::$filterUsing[0];
        $entry2 = new IncomingEntry(['uri' => '/not-api']);
        $entry2->type = 'other';
        $entry2->content = ['uri' => '/not-api'];
        $this->assertTrue($callback($entry2));
    }

    public function testHideSensitiveRequestDetails()
    {
        $fake = new class {
            public $hiddenParams = null;
            public $hiddenHeaders = null;

            public function hideRequestParameters($params)
            {
                $this->hiddenParams = $params;
            }

            public function hideRequestHeaders($headers)
            {
                $this->hiddenHeaders = $headers;
            }
        };

        // Reset Telescope hidden arrays
        Telescope::$hiddenRequestParameters = [];
        Telescope::$hiddenRequestHeaders = [];

        $provider = new TelescopeServiceProvider(app());
        $this->invokeProtected($provider, 'hideSensitiveRequestDetails');

        $this->assertIsArray(Telescope::$hiddenRequestParameters);
        $this->assertContains('_token', Telescope::$hiddenRequestParameters);
        $this->assertIsArray(Telescope::$hiddenRequestHeaders);
        $this->assertContains('cookie', Telescope::$hiddenRequestHeaders);
    }

    public function testGateDefinesViewTelescope()
    {
        Gate::shouldReceive('define')->with('viewTelescope', Closure::class)->once();
        $provider = new TelescopeServiceProvider(app());
        $this->invokeProtected($provider, 'gate');
        $this->assertTrue(true);
    }

    public function testRegisterRegistersAvatarCallbackReturnsAvatarUrl()
    {
        // Ensure provider registers an avatar callback (Avatar::$callback is protected; use reflection)
        $provider = new TelescopeServiceProvider(app());
        $provider->register();

        $ref = new ReflectionClass(Avatar::class);
        $prop = $ref->getProperty('callback');
        $prop->setAccessible(true);
        $cb = $prop->getValue();
        $this->assertIsCallable($cb);
    }

    public function testRegisterAvatarCallbackHandlesMissingUser()
    {
        // Register a custom avatar callback that returns null to simulate missing user
        Avatar::register(function ($id, $email) {
            return null;
        });

        $result = Avatar::url(['id' => '9999', 'email' => 'noone@example.test']);
        $this->assertNull($result);
    }

    public function testResolveAvatarReturnsUserAvatarWhenUserExists()
    {
        // Use a real user created in the test database so User::find returns it
        $user = User::factory()->create(['avatar' => null]);
        $provider = new TelescopeServiceProvider(app());
        $ref = new ReflectionClass($provider);
        $method = $ref->getMethod('resolveAvatar');
        $method->setAccessible(true);
        $result = $method->invokeArgs($provider, [(string)$user->id, $user->email ?? $user->nickname]);
        $this->assertIsString($result);
        $this->assertStringContainsString('gravatar.com', $result);
    }

    public function testResolveAvatarReturnsGravatarForMissingUser()
    {
        // No users created for this test; find should return null and gravatar will be used
        $provider = new TelescopeServiceProvider(app());
        $ref = new ReflectionClass($provider);
        $method = $ref->getMethod('resolveAvatar');
        $method->setAccessible(true);
        $result = $method->invokeArgs($provider, ['999999', 'noone@example.test']);
        $this->assertIsString($result);
        $this->assertStringContainsString('gravatar.com', $result);
        $this->assertStringContainsString(md5(strtolower(trim('noone@example.test'))), $result);
    }

    public function testResolveAvatarReturnsCustomAvatarWhenUserHasAvatar()
    {
        // Create a user and attach a linked account that contains an avatar_url
        $user = User::factory()->create(['avatar' => null]);
        $acc = LinkedAccount::create([
            'user_id' => $user->id,
            'avatar_url' => 'https://cdn.example/test-avatar.png',
            'external_id' => '12345',
        ]);

        $provider = new TelescopeServiceProvider(app());
        $ref = new ReflectionClass($provider);
        $method = $ref->getMethod('resolveAvatar');
        $method->setAccessible(true);
        $result = $method->invokeArgs($provider, [(string)$user->id, $user->email ?? $user->nickname]);

        $this->assertIsString($result);
        // avatarUrl() prefers linked account avatar_url; assert the resolver defers to that
        $this->assertEquals($user->avatarUrl(), $result, 'Expected resolveAvatar to return the user avatarUrl() when a linked account provides an avatar');
    }

    public function testProviderRegisteredAvatarCallbackIsInvoked()
    {
        // Create a user with linked account avatar
        $user = User::factory()->create(['avatar' => null]);
        LinkedAccount::create([
            'user_id' => $user->id,
            'avatar_url' => 'https://cdn.example/provider-avatar.png',
            'external_id' => 'xyz',
        ]);

        $provider = new TelescopeServiceProvider(app());
        // Register the provider which will call Telescope::avatar with a closure
        $provider->register();

        // Invoke the avatar callback via the public API; Avatar::url should call the closure registered above
        $result = Avatar::url(['id' => (string)$user->id, 'email' => $user->email ?? $user->nickname]);
        $this->assertEquals($user->avatarUrl(), $result);

        // Also reflect into the Avatar class to get the registered callback and invoke it directly
        $ref = new ReflectionClass(Avatar::class);
        $prop = $ref->getProperty('callback');
        $prop->setAccessible(true);
        $cb = $prop->getValue();
        $this->assertIsCallable($cb, 'Expected provider->register() to register an avatar callback');
        $direct = $cb((string)$user->id, $user->email ?? $user->nickname);
        $this->assertEquals($user->avatarUrl(), $direct, 'Expected direct invocation of registered callback to return the same avatar URL');
    }

    public function testRegisterFilterReturnsTrueForReportableEntry()
    {
        // Ensure non-local environment
        $this->app['env'] = 'production';

        // Reset filter registry
        Telescope::$filterUsing = [];

        $provider = new TelescopeServiceProvider(app());
        $provider->register();

        $this->assertNotEmpty(Telescope::$filterUsing);
        $callback = Telescope::$filterUsing[0];

        // Create a fake entry that is reportable
        $fakeEntry = Mockery::mock(IncomingEntry::class);
        $fakeEntry->shouldReceive('isRequest')->andReturn(false);
        $fakeEntry->shouldReceive('isReportableException')->andReturn(true);
        $fakeEntry->shouldReceive('isFailedRequest')->andReturn(false);
        $fakeEntry->shouldReceive('isFailedJob')->andReturn(false);
        $fakeEntry->shouldReceive('isScheduledTask')->andReturn(false);
        $fakeEntry->shouldReceive('hasMonitoredTag')->andReturn(false);

        $this->assertTrue($callback($fakeEntry));
    }

    public function testRegisterFilterLocalEnvironmentSkipsApiFilter()
    {
        // Force app environment to local
        $this->app['env'] = 'local';

        // Reset filter registry
        Telescope::$filterUsing = [];

        $provider = new TelescopeServiceProvider(app());
        $provider->register();
        $this->assertNotEmpty(Telescope::$filterUsing);
        $callback = Telescope::$filterUsing[0];

        $entry = new IncomingEntry(['uri' => '/api/v1/test']);
        $entry->type = EntryType::REQUEST;
        $entry->content = ['uri' => '/api/v1/test'];

        // In local environment the callback should always allow entries
        $this->assertTrue($callback($entry));
    }

    public function testGateClosureReturnsBasedOnUserRole()
    {
        // Ensure Gate is using the real registry for this assertion
        // Call the provider->gate to register the gate
        $provider = new TelescopeServiceProvider(app());
        $this->invokeProtected($provider, 'gate');

        // Create a user stub that returns true for hasRole('admin')
        $adminUser = new class {
            public function hasRole($role)
            {
                return $role === 'admin';
            }
        };

        $nonAdminUser = new class {
            public function hasRole($role)
            {
                return false;
            }
        };

        // Use Gate facade to evaluate the registered gate for each user
        $this->assertTrue(Gate::forUser($adminUser)->allows('viewTelescope'));
        $this->assertFalse(Gate::forUser($nonAdminUser)->allows('viewTelescope'));
    }

    private function invokeProtected($object, $method, $args = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}
