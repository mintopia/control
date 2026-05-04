<?php

namespace Tests\Unit\app\Transformers\V1;

use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatingPlan;
use App\Models\Ticket;
use App\Models\TicketProvider;
use App\Models\TicketType;
use App\Models\User;
use App\Transformers\V1\TicketTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Tests\TestCase;

class TicketTransformerTest extends TestCase
{
    use RefreshDatabase;

    public function test_transforms_ticket_with_default_includes()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $type = TicketType::factory()->create(['event_id' => $event->id]);
        $provider = TicketProvider::factory()->create();
        $user = User::factory()->withEmailAddress()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        $ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'ticket_type_id' => $type->id,
            'ticket_provider_id' => $provider->id,
            'user_id' => $user->id,
            'reference' => 'REF-1',
            'external_id' => 'EXT-1',
            'name' => 'Standard',
        ]);
        Seat::factory()->create([
            'seating_plan_id' => $plan->id,
            'ticket_id' => $ticket->id,
            'label' => 'A1',
        ]);

        $data = $this->transform($ticket->fresh(['user.primaryEmail', 'event', 'type', 'provider', 'seat']));

        $this->assertEquals($ticket->id, $data['id']);
        $this->assertEquals('REF-1', $data['reference']);
        $this->assertEquals('EXT-1', $data['external_id']);
        $this->assertEquals('Standard', $data['name']);
        $this->assertArrayHasKey('created_at', $data);

        // Event nested resource uses the full EventTransformer; assert
        // representative keys are present rather than an exact shape.
        $eventData = $this->dataOf($data['event']);
        $this->assertArrayHasKey('code', $eventData);
        $this->assertArrayHasKey('name', $eventData);

        $this->assertEquals(['id', 'name'], array_keys($this->dataOf($data['type'])));
        $this->assertEquals(['id', 'code', 'name'], array_keys($this->dataOf($data['provider'])));
        $this->assertEquals(['id', 'nickname', 'name', 'email'], array_keys($this->dataOf($data['user'])));
        $this->assertEquals(['id', 'label', 'row', 'number'], array_keys($this->dataOf($data['seat'])));
    }

    public function test_null_user_and_seat_render_as_null()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $type = TicketType::factory()->create(['event_id' => $event->id]);
        $provider = TicketProvider::factory()->create();

        $ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'ticket_type_id' => $type->id,
            'ticket_provider_id' => $provider->id,
            'user_id' => null,
        ]);

        $data = $this->transform($ticket->fresh(['user', 'event', 'type', 'provider', 'seat']));

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('seat', $data);
        $this->assertNull($this->dataOf($data['user']));
        $this->assertNull($this->dataOf($data['seat']));
    }

    protected function transform(Ticket $ticket): array
    {
        $manager = new Manager;
        $manager->parseIncludes(['event', 'type', 'provider', 'user', 'seat']);
        $resource = new Item($ticket, new TicketTransformer);

        return $manager->createData($resource)->toArray()['data'];
    }

    /**
     * Fractal nests included items under a 'data' key. Unwrap defensively
     * so the test passes whether the serializer wraps nulls or not.
     */
    protected function dataOf($value)
    {
        if (is_array($value) && array_key_exists('data', $value)) {
            return $value['data'];
        }

        return $value;
    }
}
