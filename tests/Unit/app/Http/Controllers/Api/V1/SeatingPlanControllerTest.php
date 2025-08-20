<?php

namespace Tests\Unit\app\Http\Controllers\Api\V1;

use Tests\TestCase;
use App\Http\Controllers\Api\V1\SeatingPlanController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class SeatingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new SeatingPlanController();
        $this->assertInstanceOf(SeatingPlanController::class, $controller);
    }

    public function testIndexReturnsPaginatedFractalResponse()
    {
        $event = \App\Models\Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        \App\Models\SeatingPlan::factory()->count(3)->create(['event_id' => $event->id]);

        $controller = new SeatingPlanController();
        $request = Request::create('/', 'GET', ['perPage' => 2]);
        $response = $controller->index($request, $event);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(2, $data['data']);
    }

    public function testIndexDefaultPerPageReturnsAllWhenUnderLimit()
    {
        $event = \App\Models\Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        \App\Models\SeatingPlan::factory()->count(5)->create(['event_id' => $event->id]);

        $controller = new SeatingPlanController();
        $request = Request::create('/', 'GET');
        $response = $controller->index($request, $event);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $data = $response->getData(true);
        $this->assertCount(5, $data['data']);
    }

    public function testIndexIncludesEventDataInItems()
    {
        $event = \App\Models\Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
            'name' => 'MyEvent',
        ]);
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id]);

        $controller = new SeatingPlanController();
        $request = Request::create('/', 'GET');
        $response = $controller->index($request, $event);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getData(true);
        $this->assertNotEmpty($body['data']);
        $first = $body['data'][0];
        $this->assertArrayHasKey('event', $first);
        // Fractal may nest the included resource under 'data' (event => ['data' => [...]])
        if (isset($first['event']['name'])) {
            $this->assertEquals('MyEvent', $first['event']['name']);
        } elseif (isset($first['event']['data']['name'])) {
            $this->assertEquals('MyEvent', $first['event']['data']['name']);
        } else {
            $this->fail('Event name not present in transformed seating plan item. Received: ' . json_encode($first['event']));
        }
    }

    public function testShowReturnsFractalResponse()
    {
        $event = \App\Models\Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id]);
        $controller = new SeatingPlanController();
        $response = $controller->show($plan);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getData(true);
        $this->assertArrayHasKey('data', $body);
        $this->assertEquals($plan->id, $body['data']['id']);
    }

    // Tests for index (Eloquent-backed)
    public function testIndexReturnsViewWithEventsForAdmin()
    {
        // Admin should see draft and non-draft events
        $eventDraft = \App\Models\Event::factory()->create(['draft' => true, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        $eventLive = \App\Models\Event::factory()->create(['draft' => false, 'starts_at' => now()->subDay(), 'ends_at' => now()->addHour()]);

        $controller = new \App\Http\Controllers\SeatingPlanController();
        $request = Request::create('/', 'GET');
        $userStub = new class {
            public function hasAnyRole($roles)
            {
                return true;
            }
        };
        $request->setUserResolver(function () use ($userStub) {
            return $userStub;
        });

        $response = $controller->index($request);
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $data = $response->getData();
        $this->assertArrayHasKey('events', $data);
        $events = $data['events'];
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $events);
        // both events should be present for admin
        $ids = $events->pluck('id')->all();
        $this->assertContains($eventDraft->id, $ids);
        $this->assertContains($eventLive->id, $ids);
    }

    public function testIndexReturnsViewWithEventsForNonAdmin()
    {
        // Non-admin should only see non-draft events
        $eventDraft = \App\Models\Event::factory()->create(['draft' => true, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        $eventLive = \App\Models\Event::factory()->create(['draft' => false, 'starts_at' => now()->subDay(), 'ends_at' => now()->addHour()]);

        $controller = new \App\Http\Controllers\SeatingPlanController();
        $request = Request::create('/', 'GET');
        $userStub = new class {
            public function hasAnyRole($roles)
            {
                return false;
            }
        };
        $request->setUserResolver(function () use ($userStub) {
            return $userStub;
        });

        $response = $controller->index($request);
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $data = $response->getData();
        $this->assertArrayHasKey('events', $data);
        $events = $data['events'];
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $events);
        $ids = $events->pluck('id')->all();
        $this->assertNotContains($eventDraft->id, $ids);
        $this->assertContains($eventLive->id, $ids);
    }

    public function testSelectAbortsIfSeatPlanEventMismatch()
    {
        $controller = new \App\Http\Controllers\SeatingPlanController();
        $event1 = \App\Models\Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()]);
        $event2 = \App\Models\Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()]);

        $planForEvent2 = \App\Models\SeatingPlan::factory()->create(['event_id' => $event2->id]);
        $seat = \App\Models\Seat::factory()->create(['seating_plan_id' => $planForEvent2->id]);
        $ticket = \App\Models\Ticket::factory()->create(['event_id' => $event1->id]);

        $request = Request::create('/', 'GET');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->select($request, $event1, $ticket, $seat);
    }

    public function testSelectAbortsIfTicketEventMismatch()
    {
        $controller = new \App\Http\Controllers\SeatingPlanController();
        $event1 = \App\Models\Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()]);
        $event2 = \App\Models\Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()]);

        $planForEvent1 = \App\Models\SeatingPlan::factory()->create(['event_id' => $event1->id]);
        $seat = \App\Models\Seat::factory()->create(['seating_plan_id' => $planForEvent1->id]);
        $ticketForEvent2 = \App\Models\Ticket::factory()->create(['event_id' => $event2->id]);

        $request = Request::create('/', 'GET');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->select($request, $event1, $ticketForEvent2, $seat);
    }

    public function testShowSetsInfoMessageWhenSeatingLocked()
    {
        $controller = new \App\Http\Controllers\SeatingPlanController();
        $event = \App\Models\Event::factory()->create(['seating_locked' => true, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        $user = \App\Models\User::factory()->create();

        $request = Request::create('/', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $request->setLaravelSession(app('session.store'));

        $response = $controller->show($request, $event, null);
        // show returns a view; ensure session has the infoMessage set
        $this->assertEquals('Seating is locked', $request->session()->get('infoMessage'));
    }
}
