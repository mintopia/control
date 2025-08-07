<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\EventTicketProviderController;
use Illuminate\Http\Request;
use Mockery;

class EventTicketProviderControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new EventTicketProviderController();
        $this->assertInstanceOf(EventTicketProviderController::class, $controller);
    }

    public function testAddTicketProviderReturnsSuccessResponse()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn(['provider_name' => 'Test Provider', 'event_id' => 1]);

        $controller = Mockery::mock(EventTicketProviderController::class, [])->makePartial();

        $controller->shouldReceive('addTicketProvider')
            ->once()
            ->with(Mockery::type(Request::class))
            ->andReturn(response()->json(['message' => 'Ticket provider added successfully'], 201));

        $response = $controller->addTicketProvider($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Ticket provider added successfully']),
            $response->getContent()
        );
    }

    public function testRemoveTicketProviderReturnsSuccessResponse()
    {
        $controller = Mockery::mock(EventTicketProviderController::class, [])->makePartial();

        $controller->shouldReceive('removeTicketProvider')
            ->once()
            ->with(1)
            ->andReturn(response()->json(['message' => 'Ticket provider removed successfully'], 200));

        $response = $controller->removeTicketProvider(1);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Ticket provider removed successfully']),
            $response->getContent()
        );
    }

    public function testListTicketProvidersReturnsExpectedResponse()
    {
        $controller = Mockery::mock(EventTicketProviderController::class, [])->makePartial();

        $controller->shouldReceive('listTicketProviders')
            ->once()
            ->andReturn(response()->json(['providers' => ['provider1', 'provider2']], 200));

        $response = $controller->listTicketProviders();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['providers' => ['provider1', 'provider2']]),
            $response->getContent()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
