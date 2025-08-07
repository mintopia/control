<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\EventMappingController;
use Illuminate\Http\Request;
use Mockery;

class EventMappingControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new EventMappingController();
        $this->assertInstanceOf(EventMappingController::class, $controller);
    }

    public function testMapEventReturnsSuccessResponse()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn(['event_id' => 1, 'mapping' => 'Test Mapping']);

        $controller = Mockery::mock(EventMappingController::class, [])->makePartial();

        $controller->shouldReceive('mapEvent')
            ->once()
            ->with(Mockery::type(Request::class))
            ->andReturn(response()->json(['message' => 'Event mapped successfully'], 200));

        $response = $controller->mapEvent($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Event mapped successfully']),
            $response->getContent()
        );
    }

    public function testUnmapEventReturnsSuccessResponse()
    {
        $controller = Mockery::mock(EventMappingController::class, [])->makePartial();

        $controller->shouldReceive('unmapEvent')
            ->once()
            ->with(1)
            ->andReturn(response()->json(['message' => 'Event unmapped successfully'], 200));

        $response = $controller->unmapEvent(1);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Event unmapped successfully']),
            $response->getContent()
        );
    }

    public function testListMappingsReturnsExpectedResponse()
    {
        $controller = Mockery::mock(EventMappingController::class, [])->makePartial();

        $controller->shouldReceive('listMappings')
            ->once()
            ->andReturn(response()->json(['mappings' => ['mapping1', 'mapping2']], 200));

        $response = $controller->listMappings();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['mappings' => ['mapping1', 'mapping2']]),
            $response->getContent()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
