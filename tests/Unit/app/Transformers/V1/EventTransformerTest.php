<?php

namespace Tests\Unit\app\Transformers\V1;

use App\Models\Event;
use App\Models\User;
use App\Transformers\V1\EventTransformer;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EventTransformerTest extends TestCase
{
    public function testTransformReturnsExpectedData()
    {
        $event = new Event();
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
}
