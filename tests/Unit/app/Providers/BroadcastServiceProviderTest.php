<?php

namespace Tests\Unit\app\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Tests\TestCase;
use Mockery;

class BroadcastServiceProviderTest extends TestCase
{
    public function testBootRegistersBroadcastRoutesAndRequiresChannelsFile()
    {
        Broadcast::shouldReceive('routes')->once();
        // We can't easily test require base_path('routes/channels.php') without integration, but we can check no exceptions
        $provider = new \app\Providers\BroadcastServiceProvider(app());
        $provider->boot();
        $this->assertTrue(true); // If no exception, pass
    }

    public function testProviderIsInstanceOfServiceProvider()
    {
        $provider = new \app\Providers\BroadcastServiceProvider(app());
        $this->assertInstanceOf(ServiceProvider::class, $provider);
    }

    // FIXME
    // public function testBootDoesNotThrowIfChannelsFileMissing()
    // {
    //     Broadcast::shouldReceive('routes')->once();
    //     // Temporarily override base_path to a non-existent file
    //     $provider = Mockery::mock(\app\Providers\BroadcastServiceProvider::class, [app()])
    //         ->makePartial()
    //         ->shouldAllowMockingProtectedMethods();

    //     // Mock the global base_path function
    //     $basePath = base_path('routes/channels.php');
    //     $mockedBasePath = $basePath . '.notfound';
    //     $provider->shouldReceive('boot')->andReturnUsing(function () use ($mockedBasePath) {
    //         Broadcast::routes();
    //         // Simulate require of missing file
    //         @require $mockedBasePath;
    //     });

    //     $provider->boot();
    //     $this->assertTrue(true);
    // }

    public function testBootCallsBroadcastRoutesExactlyOnce()
    {
        Broadcast::shouldReceive('routes')->once();
        $provider = new \app\Providers\BroadcastServiceProvider(app());
        $provider->boot();
        $this->assertTrue(true);
    }
}
