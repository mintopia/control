<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new HomeController();
        $this->assertInstanceOf(HomeController::class, $controller);
    }

    public function testHomeReturnsViewWithTicketsAndEvents()
    {
        $user = User::factory()->create();

        // create an event and a ticket belonging to the user
        $event = Event::factory()->create([
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'draft' => false,
        ]);

        // Ticket factory may exist; if not this will fail and tests will indicate needed factories
        if (class_exists(\App\Models\Ticket::class)) {
            Ticket::factory()->create([
                'user_id' => $user->id,
                'event_id' => $event->id,
            ]);
        }

        $request = Request::create('/', 'GET');
        $request->setLaravelSession(app('session.store'));
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $controller = new HomeController();

        $response = $controller->home($request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);

        $data = $response->getData();

        $this->assertArrayHasKey('tickets', $data);
        $this->assertArrayHasKey('events', $data);
        $this->assertNotEmpty($data['events']);
    }
}
