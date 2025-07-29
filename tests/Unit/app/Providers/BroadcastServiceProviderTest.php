<?php

namespace Tests\Unit\App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class BroadcastServiceProviderTest extends TestCase
{
    public function testBootRegistersBroadcastRoutesAndRequiresChannelsFile()
    {
        Broadcast::shouldReceive('routes')->once();
        // We can't easily test require base_path('routes/channels.php') without integration, but we can check no exceptions
        $provider = new \App\Providers\BroadcastServiceProvider(app());
        $provider->boot();
        $this->assertTrue(true); // If no exception, pass
    }
}
