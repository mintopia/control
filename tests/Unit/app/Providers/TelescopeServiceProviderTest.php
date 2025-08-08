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

    // FIXME Facade being used does not implement the required getFacadeAccessor method?
    /*
    use Illuminate\Support\Facades\Gate;
    use Laravel\Telescope\IncomingEntry;
    use Laravel\Telescope\Facades\Telescope;
    use Laravel\Telescope\TelescopeApplicationServiceProvider;

    class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
    {
        public function register()
        {
            parent::register();
            Telescope::night();
            Telescope::filter(function (IncomingEntry $entry) {
                if ($this->app->environment('local')) {
                    return true;
                }

                // Filter out the seating plan API
                if ($entry->isRequest() && str_starts_with($entry->content['uri'], '/api/v1/')) {
                    return false;
                }

                if (config('telescope.nofilter', false)) {
                    return true;
                }

                return $entry->isReportableException() ||
                    $entry->isFailedRequest() ||
                    $entry->isFailedJob() ||
                    $entry->isScheduledTask() ||
                    $entry->hasMonitoredTag();
            });
            Telescope::avatar(function ($id, $email) {
                // Provide a default avatar logic or mock as needed
                return null;
            });
        }

        protected function hideSensitiveRequestDetails()
        {
            Telescope::hideRequestParameters(['_token']);
            Telescope::hideRequestHeaders(['cookie', 'x-csrf-token', 'x-xsrf-token']);
        }

        protected function gate()
        {
            Gate::define('viewTelescope', function ($user) {
                // Provide logic for viewing Telescope, e.g., only admin users
                return true;
            });
        }
    }
    */
    public function testRegisterConfiguresTelescope()
    {

        Facade::shouldReceive('getFacadeApplication')->andReturn(app());
        Facade::clearResolvedInstance('telescope');
        $telescopeMock = \Mockery::mock('overload:Laravel\Telescope\Telescope');
        $telescopeMock->shouldReceive('night')->once();
        $telescopeMock->shouldReceive('filter')->once();
        $telescopeMock->shouldReceive('avatar')->once();
        $provider = new \app\Providers\TelescopeServiceProvider(app());
        $provider->register();
        $this->assertTrue(true);
    }

    public function testHideSensitiveRequestDetails()
    {
        $telescopeMock = \Mockery::mock('overload:Laravel\Telescope\Telescope');
        $telescopeMock->shouldReceive('hideRequestParameters')->once();
        $telescopeMock->shouldReceive('hideRequestHeaders')->once();
        $provider = new \app\Providers\TelescopeServiceProvider(app());
        $this->invokeProtected($provider, 'hideSensitiveRequestDetails');
        $this->assertTrue(true);
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
