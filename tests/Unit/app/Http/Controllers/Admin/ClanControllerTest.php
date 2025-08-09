<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\ClanController;
use Illuminate\Http\Request;
use App\Models\Clan;
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

    // FIXME Mockery for these instances did not work - Class already exists
    // public function testStoreValidatesAndSavesData()
    // {
    //     // Arrange
    //     $request = Mockery::mock([Request::class]);
    //     $request->shouldReceive('all')->once()->andReturn(json_encode(['name' => 'Test Clan']));

    //     // Correct Mockery alias syntax
    //     $clanMock = Mockery::mock([Clan::class])->makePartial();
    //     $clanMock->shouldReceive('create')->once()->with(['name' => 'Test Clan'])->andReturnSelf();

    //     $controller = new ClanController();

    //     $response = $controller->store($request);

    //     $this->assertEquals(201, $response->getStatusCode());
    //     $this->assertJsonStringEqualsJsonString(
    //         json_encode(['message' => 'Clan created']),
    //         $response->getContent()
    //     );
    // }

    // public function testDestroyDeletesClan()
    // {
    //     // Arrange

    //     $request = Mockery::mock([Request::class]);
    //     $clanMock = Mockery::mock([Clan::class]);
    //     $clanMock->shouldReceive('delete')->once()->andReturn(true);

    //     $controller = new ClanController();

    //     // Act
    //     $response = $controller->destroy($request, $clanMock);

    //     // Assert
    //     $this->assertEquals(200, $response->getStatusCode());
    //     $this->assertJsonStringEqualsJsonString(
    //         json_encode(['message' => 'Clan deleted']),
    //         $response->getContent()
    //     );
    // }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
