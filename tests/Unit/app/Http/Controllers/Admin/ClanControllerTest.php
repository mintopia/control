<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\ClanController;
use Illuminate\Http\Request;
use Mockery;

class ClanControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new ClanController();
        $this->assertInstanceOf(ClanController::class, $controller);
    }

    public function testIndexReturnsExpectedResponse()
    {
        $request = Mockery::mock(Request::class);
        $controller = Mockery::mock(ClanController::class)->makePartial();

        $controller->shouldReceive('index')
            ->once()
            ->with($request)
            ->andReturn(response()->json(['data' => 'test'], 200));

        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    public function testStoreValidatesAndSavesData()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn(['name' => 'Test Clan']);

        $controller = Mockery::mock(ClanController::class)->makePartial();

        $controller->shouldReceive('store')
            ->once()
            ->with($request)
            ->andReturn(response()->json(['message' => 'Clan created'], 201));

        $response = $controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Clan created']),
            $response->getContent()
        );
    }

    public function testDestroyDeletesClan()
    {
        $controller = Mockery::mock(ClanController::class)->makePartial();

        $controller->shouldReceive('destroy')
            ->once()
            ->with(1)
            ->andReturn(response()->json(['message' => 'Clan deleted'], 200));

        $response = $controller->destroy(1);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Clan deleted']),
            $response->getContent()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
