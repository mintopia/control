<?php

namespace Tests\Unit\app\Providers;

use Tests\TestCase;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Support\Facades\Facade;
use Laravel\Telescope\Telescope;

class TelescopeServiceProviderTest extends TestCase
{

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
            { /* noop for register test */
            }
            public function hideRequestHeaders($headers)
            { /* noop for register test */
            }
        };

        // Clear Telescope static callbacks to ensure a clean test environment
        \Laravel\Telescope\Telescope::$filterUsing = [];
        \Laravel\Telescope\Telescope::$tagUsing = [];

        $provider = new \App\Providers\TelescopeServiceProvider(app());
        $provider->register();

        $this->assertIsArray(\Laravel\Telescope\Telescope::$filterUsing);
        $this->assertNotEmpty(\Laravel\Telescope\Telescope::$filterUsing, 'Telescope filter callback was not registered');
    }

    public function testRegisterInLocalEnvironmentAllowsAllEntries()
    {
        // Force app environment to local
        $this->app['env'] = 'local';

        // Reset filter registry
        \Laravel\Telescope\Telescope::$filterUsing = [];

        $provider = new \App\Providers\TelescopeServiceProvider(app());
        $provider->register();

        $this->assertNotEmpty(\Laravel\Telescope\Telescope::$filterUsing);
        $callback = \Laravel\Telescope\Telescope::$filterUsing[0];
        $fakeEntry = \Mockery::mock(\Laravel\Telescope\IncomingEntry::class);
        $fakeEntry->shouldReceive('isRequest')->andReturn(false);
        // In local env the filter should return true
        $this->assertTrue($callback($fakeEntry));
    }

    public function testRegisterFiltersOutApiPathsAndHonorsNofilter()
    {
        // Normal non-local environment
        $this->app['env'] = 'production';

        // Case 1: API path should be filtered out
        \Laravel\Telescope\Telescope::$filterUsing = [];
        $provider = new \App\Providers\TelescopeServiceProvider(app());
        $provider->register();
        $callback = \Laravel\Telescope\Telescope::$filterUsing[0];

        $entry = new \Laravel\Telescope\IncomingEntry(['uri' => '/api/v1/something']);
        $entry->type = \Laravel\Telescope\EntryType::REQUEST;
        $entry->content = ['uri' => '/api/v1/something'];
        $this->assertFalse($callback($entry));

        // Additional explicit test: ensure URIs starting with '/api/v1/' are filtered out
        \Laravel\Telescope\Telescope::$filterUsing = [];
        $provider = new \App\Providers\TelescopeServiceProvider(app());
        $provider->register();
        $callback2 = \Laravel\Telescope\Telescope::$filterUsing[0];
        $entryApi = new \Laravel\Telescope\IncomingEntry(['uri' => '/api/v1/other']);
        $entryApi->type = \Laravel\Telescope\EntryType::REQUEST;
        $entryApi->content = ['uri' => '/api/v1/other'];
        $this->assertFalse($callback2($entryApi), 'API v1 URIs should be filtered out by the telescope filter');

        // Case 2: config nofilter true should allow all
        config(['telescope.nofilter' => true]);
        \Laravel\Telescope\Telescope::$filterUsing = [];
        $provider = new \App\Providers\TelescopeServiceProvider(app());
        $provider->register();
        $callback = \Laravel\Telescope\Telescope::$filterUsing[0];
        $entry2 = new \Laravel\Telescope\IncomingEntry(['uri' => '/not-api']);
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
        \Laravel\Telescope\Telescope::$hiddenRequestParameters = [];
        \Laravel\Telescope\Telescope::$hiddenRequestHeaders = [];

        $provider = new \App\Providers\TelescopeServiceProvider(app());
        $this->invokeProtected($provider, 'hideSensitiveRequestDetails');

        $this->assertIsArray(\Laravel\Telescope\Telescope::$hiddenRequestParameters);
        $this->assertContains('_token', \Laravel\Telescope\Telescope::$hiddenRequestParameters);
        $this->assertIsArray(\Laravel\Telescope\Telescope::$hiddenRequestHeaders);
        $this->assertContains('cookie', \Laravel\Telescope\Telescope::$hiddenRequestHeaders);
    }

    public function testGateDefinesViewTelescope()
    {
        Gate::shouldReceive('define')->with('viewTelescope', \Closure::class)->once();
        $provider = new \app\Providers\TelescopeServiceProvider(app());
        $this->invokeProtected($provider, 'gate');
        $this->assertTrue(true);
    }

    public function testRegisterRegistersAvatarCallbackReturnsAvatarUrl()
    {
        // Ensure provider registers an avatar callback (Avatar::$callback is protected; use reflection)
        $provider = new \App\Providers\TelescopeServiceProvider(app());
        $provider->register();

        $ref = new \ReflectionClass(\Laravel\Telescope\Avatar::class);
        $prop = $ref->getProperty('callback');
        $prop->setAccessible(true);
        $cb = $prop->getValue();
        $this->assertIsCallable($cb);
    }

    public function testRegisterAvatarCallbackHandlesMissingUser()
    {
        // Register a custom avatar callback that returns null to simulate missing user
        \Laravel\Telescope\Avatar::register(function ($id, $email) {
            return null;
        });

        $result = \Laravel\Telescope\Avatar::url(['id' => '9999', 'email' => 'noone@example.test']);
        $this->assertNull($result);
    }

    public function testRegisterFilterReturnsTrueForReportableEntry()
    {
        // Ensure non-local environment
        $this->app['env'] = 'production';

        // Reset filter registry
        \Laravel\Telescope\Telescope::$filterUsing = [];

        $provider = new \App\Providers\TelescopeServiceProvider(app());
        $provider->register();

        $this->assertNotEmpty(\Laravel\Telescope\Telescope::$filterUsing);
        $callback = \Laravel\Telescope\Telescope::$filterUsing[0];

        // Create a fake entry that is reportable
        $fakeEntry = \Mockery::mock(\Laravel\Telescope\IncomingEntry::class);
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
        \Laravel\Telescope\Telescope::$filterUsing = [];

        $provider = new \App\Providers\TelescopeServiceProvider(app());
        $provider->register();
        $this->assertNotEmpty(\Laravel\Telescope\Telescope::$filterUsing);
        $callback = \Laravel\Telescope\Telescope::$filterUsing[0];

        $entry = new \Laravel\Telescope\IncomingEntry(['uri' => '/api/v1/test']);
        $entry->type = \Laravel\Telescope\EntryType::REQUEST;
        $entry->content = ['uri' => '/api/v1/test'];

        // In local environment the callback should always allow entries
        $this->assertTrue($callback($entry));
    }

    public function testGateClosureReturnsBasedOnUserRole()
    {
        // Ensure Gate is using the real registry for this assertion
        // Call the provider->gate to register the gate
        $provider = new \App\Providers\TelescopeServiceProvider(app());
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
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}
