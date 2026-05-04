<?php

namespace Tests\Feature\app\Http\Controllers\Api\V1;

use App\Models\ApiKey;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns401_without_authorization_header()
    {
        $this->getJson('/api/v1/events')->assertStatus(401);
    }

    public function test_index_returns401_for_unknown_token()
    {
        $this->getJson('/api/v1/events', ['Authorization' => 'Bearer ctrl_unknown'])
            ->assertStatus(401);
    }

    public function test_index_returns401_for_disabled_key()
    {
        $plaintext = '';
        ApiKey::factory()->disabled()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $this->getJson('/api/v1/events', ['Authorization' => "Bearer {$plaintext}"])
            ->assertStatus(401);
    }

    public function test_index_returns200_with_valid_key()
    {
        $plaintext = '';
        ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        $response = $this->getJson('/api/v1/events', ['Authorization' => "Bearer {$plaintext}"]);
        $response->assertOk();
        $response->assertJsonStructure(['data' => [['id', 'code', 'name']]]);
    }

    public function test_show_returns_event_by_code()
    {
        $plaintext = '';
        ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        $this->getJson("/api/v1/events/{$event->code}", ['Authorization' => "Bearer {$plaintext}"])
            ->assertOk()
            ->assertJsonPath('data.code', $event->code);
    }
}
