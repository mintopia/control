<?php

namespace Tests\Unit\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\SyncTickets;
use App\Models\TicketProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SyncTicketsTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateCommand()
    {
        $command = new SyncTickets();
        $this->assertInstanceOf(SyncTickets::class, $command);
    }

    public function testHandleWithNoProvidersDoesNothing()
    {
        $command = new SyncTickets();
        $command->handle();
        $this->assertDatabaseCount('ticket_providers', 0);
    }

    public function testHandleWithEnabledProvidersCallsSync()
    {
        $provider = TicketProvider::factory()->create(['enabled' => true]);
        $mock = $this->getMockBuilder(\App\Services\TicketProviders\FakeProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['syncAllTickets'])
            ->getMock();
        $mock->expects($this->once())->method('syncAllTickets');
        // Swap provider_class to our mock
        $provider->provider_class = get_class($mock);
        $provider->save();
        // Use Laravel's container to bind the mock
        app()->instance(get_class($mock), $mock);
        $command = new SyncTickets();
        $command->handle();
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
        $provider1->provider_class = get_class($mock);
        $provider1->save();
        app()->instance(get_class($mock), $mock);
        $command = new SyncTickets();
        // Simulate argument
        $command->setLaravel(app());
        $command->call('control:sync-tickets', ['provider' => 'foo']);
    }
}
