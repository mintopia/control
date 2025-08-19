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

        // Set provider_class to the concrete provider class and bind the mock instance
        $providerClass = \App\Services\TicketProviders\FakeProvider::class;
        $provider->update(['provider_class' => $providerClass]);

        // Bind via a factory so container->make($providerClass) returns our mock instance
        $this->app->bind($providerClass, function () use ($mock) {
            return $mock;
        });

        // Sanity check: container resolution should return the same mock
        $this->assertSame($mock, app($providerClass));

        // When the command runs it should resolve the mock from the container and call syncAllTickets
        $this->artisan('control:sync-tickets')->assertExitCode(0);
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

        $providerClass = \App\Services\TicketProviders\FakeProvider::class;
        $provider1->update(['provider_class' => $providerClass]);
        $this->app->bind($providerClass, function () use ($mock) {
            return $mock;
        });

        $this->assertSame($mock, app($providerClass));

        $this->artisan('control:sync-tickets', ['provider' => 'foo'])->assertExitCode(0);
    }
}
