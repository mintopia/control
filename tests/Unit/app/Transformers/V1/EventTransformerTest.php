<?php

namespace Tests\Unit\app\Transformers\V1;

use App\Models\ApiKey;
use App\Models\Event;
use App\Models\User;
use App\Transformers\V1\EventTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Tests\TestCase;

class EventTransformerTest extends TestCase
{
    use RefreshDatabase;

    public function test_transform_returns_expected_data()
    {
        $event = new Event;
        $event->code = 'E123';
        $event->name = 'Test Event';
        $event->starts_at = Carbon::parse('2022-01-01T10:00:00Z');
        $event->ends_at = Carbon::parse('2022-01-01T12:00:00Z');
        $event->seating_locked = true;
        $user = $this->createMock(User::class);
        $user->method('hasRole')->willReturn(false);
        $transformer = new EventTransformer($user);
        $result = $transformer->transform($event);
        $this->assertEquals([
            'code' => 'E123',
            'name' => 'Test Event',
            'starts_at' => '2022-01-01T10:00:00+00:00',
            'ends_at' => '2022-01-01T12:00:00+00:00',
            'seating_locked' => true,
        ], $result);
    }

    public function test_public_payload_omits_admin_fields()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
            'draft' => true,
            'boxoffice_url' => 'https://example.com',
        ]);

        $data = $this->transformItem(new EventTransformer, $event);

        $this->assertArrayNotHasKey('id', $data);
        $this->assertArrayNotHasKey('draft', $data);
        $this->assertArrayNotHasKey('boxoffice_url', $data);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('name', $data);
    }

    public function test_api_key_viewer_sees_full_admin_payload()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
            'draft' => true,
            'boxoffice_url' => 'https://example.com',
            'seating_opens_at' => now(),
            'seating_closes_at' => now()->addHour(),
        ]);
        $apiKey = ApiKey::factory()->create();

        $data = $this->transformItem(new EventTransformer(null, $apiKey), $event);

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($event->id, $data['id']);
        $this->assertArrayHasKey('draft', $data);
        $this->assertEquals($event->draft, $data['draft']);
        $this->assertArrayHasKey('boxoffice_url', $data);
        $this->assertArrayHasKey('seating_opens_at', $data);
        $this->assertArrayHasKey('seating_closes_at', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }

    protected function transformItem(EventTransformer $transformer, Event $event): array
    {
        $manager = new Manager;
        $resource = new Item($event, $transformer);

        return $manager->createData($resource)->toArray()['data'];
    }
}
