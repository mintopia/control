<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketSortingTest extends TestCase
{
    use RefreshDatabase;

    public function testTicketsPageRendersSortableReferenceHeaderLink()
    {
        $user = User::factory()->create(['first_login' => false]);
        $event = Event::factory()->create(['draft' => false, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'reference' => 'AAA-001']);

        $response = $this->actingAs($user)->get(route('tickets.index'));

        // assertOk should hold even BEFORE the view change (plain-text headers still render 200).
        // If this fails before you touch the view, it is an environment/layout problem to resolve first,
        // not part of this feature.
        $response->assertOk();

        // This is the real red -> green assertion for this task: the Reference header must link with order=reference.
        // On the default request the pagination links use order=event, so this string only appears once the
        // Reference sort header exists.
        $response->assertSee('order=reference', false);
    }

    public function testTicketsPageOrdersRowsByReferenceAscending()
    {
        $user = User::factory()->create(['first_login' => false]);
        $event = Event::factory()->create(['draft' => false, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'reference' => 'AAA-001']);
        Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'reference' => 'ZZZ-999']);

        $response = $this->actingAs($user)->get(route('tickets.index', ['order' => 'reference', 'order_direction' => 'asc']));

        $response->assertOk();
        $response->assertSeeInOrder(['AAA-001', 'ZZZ-999']);
    }

    public function testTicketsPageRejectsUnknownOrderColumn()
    {
        $user = User::factory()->create(['first_login' => false]);

        $response = $this->actingAs($user)->get(route('tickets.index', ['order' => 'bogus']));

        $response->assertSessionHasErrors('order');
    }

    public function testTicketsPageRejectsUnknownOrderDirection()
    {
        $user = User::factory()->create(['first_login' => false]);

        $response = $this->actingAs($user)->get(route('tickets.index', ['order_direction' => 'sideways']));

        $response->assertSessionHasErrors('order_direction');
    }
}
