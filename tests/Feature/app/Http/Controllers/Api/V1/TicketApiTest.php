<?php

namespace Tests\Feature\app\Http\Controllers\Api\V1;

use App\Models\ApiKey;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns401_without_authorization_header()
    {
        $this->getJson('/api/v1/tickets')->assertStatus(401);
    }

    public function test_index_returns200_and_tickets_with_valid_key()
    {
        $plaintext = '';
        ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        Ticket::factory()->create(['event_id' => $event->id]);

        $response = $this->getJson('/api/v1/tickets', ['Authorization' => "Bearer {$plaintext}"]);
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'reference', 'event', 'type', 'provider', 'user', 'seat']],
        ]);
    }

    public function test_index_filter_by_event_code()
    {
        $plaintext = '';
        ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $eventA = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $eventB = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        Ticket::factory()->count(2)->create(['event_id' => $eventA->id]);
        Ticket::factory()->count(3)->create(['event_id' => $eventB->id]);

        $response = $this->getJson(
            "/api/v1/tickets?event={$eventA->code}",
            ['Authorization' => "Bearer {$plaintext}"]
        );
        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }
}
