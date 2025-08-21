<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Models\Event;
use App\Models\EventMapping;
use App\Models\TicketProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventTicketProviderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        // Controller is intentionally empty; ensure it can be instantiated
        $controller = new \App\Http\Controllers\Admin\EventTicketProviderController();
        $this->assertInstanceOf(\App\Http\Controllers\Admin\EventTicketProviderController::class, $controller);
    }

    public function testAttachTicketProviderCreatesRecord()
    {
        $event = Event::factory()->create();
        $provider = TicketProvider::factory()->create();

        // Create mapping via factory (equivalent to attaching a ticket provider to an event)
        $mapping = EventMapping::factory()->for($event)->for($provider, 'provider')->create();

        $this->assertDatabaseHas('event_mappings', ['id' => $mapping->id, 'event_id' => $event->id, 'ticket_provider_id' => $provider->id]);
    }

    public function testDetachTicketProviderDeletesRecord()
    {
        $event = Event::factory()->create();
        $provider = TicketProvider::factory()->create();
        $mapping = EventMapping::factory()->for($event)->for($provider, 'provider')->create();

        $mapping->delete();

        $this->assertDatabaseMissing('event_mappings', ['id' => $mapping->id]);
    }

    public function testListReturnsEventMappings()
    {
        $event = Event::factory()->create();
        $provider1 = TicketProvider::factory()->create();
        $provider2 = TicketProvider::factory()->create();

        EventMapping::factory()->for($event)->for($provider1, 'provider')->create();
        EventMapping::factory()->for($event)->for($provider2, 'provider')->create();

        $mappings = $event->mappings()->with('provider')->get();

        $this->assertCount(2, $mappings);
        $this->assertEqualsCanonicalizing([$provider1->id, $provider2->id], $mappings->pluck('ticket_provider_id')->toArray());
    }
}
