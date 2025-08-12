<?php

namespace Tests\Feature\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\SyncTickets;
use App\Models\TicketProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SyncTicketsTest extends TestCase
{
    use RefreshDatabase;

    // TODO Tests do not work with the current setup, need to fix
    public function testCanInstantiateCommand()
    {
        $command = new SyncTickets();
        $this->assertInstanceOf(SyncTickets::class, $command);
    }

    public function testHandleWithNoProvidersDoesNothing()
    {
        $this->artisan('control:sync-tickets');
        $this->assertDatabaseCount('ticket_providers', 0);
    }

    public function testHandleWithEnabledProvidersCallsSync()
    {
        $provider = TicketProvider::factory()->create(['enabled' => true]);

        // Use the real getProvider method, but mock the provider class it returns
        $mock = $this->getMockBuilder(\App\Services\TicketProviders\FakeProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['syncAllTickets'])
            ->getMock();

        $mock->expects($this->once())->method('syncAllTickets');

        // Set provider_class to the mock's class and bind the mock in the container
        $provider->update(['provider_class' => get_class($mock)]);
        $this->app->instance(get_class($mock), $mock);

        // Now when getProvider() is called, it will resolve the mock from the container
        $this->artisan('control:sync-tickets');
    }

    public function testHandleWithProviderArgumentOnlySyncsThatProvider()
    {
        $provider1 = TicketProvider::factory()->create(['enabled' => true, 'code' => 'foo']);
        $provider2 = TicketProvider::factory()->create(['enabled' => true, 'code' => 'bar']);

        $mock = $this->getMockBuilder(\App\Services\TicketProviders\FakeProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['syncAllTickets'])
            ->getMock();

        $mock->expects($this->once())->method('syncAllTickets');

        $provider1->update(['provider_class' => get_class($mock)]);

        $this->app->instance(get_class($mock), $mock);

        $this->artisan('control:sync-tickets', ['provider' => 'foo']);
    }
}
