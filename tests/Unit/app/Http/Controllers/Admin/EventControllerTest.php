<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\EventController;
use Illuminate\Http\Request;
use Mockery;

class EventControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new EventController();
        $this->assertInstanceOf(EventController::class, $controller);
    }

    // FIXME Mockery for these instances did not work - Class already exists
    // public function testCreateEventReturnsSuccessResponse()
    // {
    //     $request = Mockery::mock(Request::class);
    //     $request->shouldReceive('all')->once()->andReturn(['name' => 'Test Event', 'date' => '2025-08-07']);

    //     $controller = Mockery::mock(EventController::class, [])->makePartial();

    //     $controller->shouldReceive('createEvent')
    //         ->once()
    //         ->with(Mockery::type(Request::class))
    //         ->andReturn(response()->json(['message' => 'Event created successfully'], 201));

    //     $response = $controller->createEvent($request);

    //     $this->assertEquals(201, $response->getStatusCode());
    //     $this->assertJsonStringEqualsJsonString(
    //         json_encode(['message' => 'Event created successfully']),
    //         $response->getContent()
    //     );
    // }

    // public function testUpdateEventReturnsSuccessResponse()
    // {
    //     $request = Mockery::mock(Request::class);
    //     $request->shouldReceive('all')->once()->andReturn(['name' => 'Updated Event', 'date' => '2025-08-08']);

    //     $controller = Mockery::mock(EventController::class, [])->makePartial();

    //     $controller->shouldReceive('updateEvent')
    //         ->once()
    //         ->with(1, Mockery::type(Request::class))
    //         ->andReturn(response()->json(['message' => 'Event updated successfully'], 200));

    //     $response = $controller->updateEvent(1, $request);

    //     $this->assertEquals(200, $response->getStatusCode());
    //     $this->assertJsonStringEqualsJsonString(
    //         json_encode(['message' => 'Event updated successfully']),
    //         $response->getContent()
    //     );
    // }

    // public function testDeleteEventReturnsSuccessResponse()
    // {
    //     $controller = Mockery::mock(EventController::class, [])->makePartial();

    //     $controller->shouldReceive('deleteEvent')
    //         ->once()
    //         ->with(1)
    //         ->andReturn(response()->json(['message' => 'Event deleted successfully'], 200));

    //     $response = $controller->deleteEvent(1);

    //     $this->assertEquals(200, $response->getStatusCode());
    //     $this->assertJsonStringEqualsJsonString(
    //         json_encode(['message' => 'Event deleted successfully']),
    //         $response->getContent()
    //     );
    // }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
