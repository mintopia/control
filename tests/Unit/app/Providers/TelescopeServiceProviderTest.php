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

    private function invokeProtected($object, $method, $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}
