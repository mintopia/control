<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\SeatingPlanController;
use App\Models\Event;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\SeatingPlan;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class SeatingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new SeatingPlanController();
        $this->assertInstanceOf(SeatingPlanController::class, $controller);
    }

    public function testIndexReturnsView()
    {
        $user = User::factory()->create();
        Auth::shouldReceive('user')->andReturn($user);
        $request = new \Illuminate\Http\Request();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $request->setLaravelSession(app('session.store'));
        $controller = new SeatingPlanController();
        $response = $controller->index($request);
        $this->assertTrue(is_object($response));
    }

    public function testShowReturnsView()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $request = new \Illuminate\Http\Request();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $request->setLaravelSession(app('session.store'));
        $controller = new SeatingPlanController();
        $response = $controller->show($request, $event);
        $this->assertTrue(is_object($response));
    }
}
