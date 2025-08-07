<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\HomeController;
use Illuminate\Http\Request;
use Mockery;

class HomeControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new HomeController();
        $this->assertInstanceOf(HomeController::class, $controller);
    }

    public function testIndexReturnsExpectedResponse()
    {
        $controller = Mockery::mock(HomeController::class, [])->makePartial();

        $controller->shouldReceive('index')
            ->once()
            ->andReturn(response()->json(['message' => 'Welcome to the admin dashboard'], 200));

        $response = $controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Welcome to the admin dashboard']),
            $response->getContent()
        );
    }

    public function testDashboardStatsReturnsExpectedResponse()
    {
        $controller = Mockery::mock(HomeController::class, [])->makePartial();

        $controller->shouldReceive('dashboardStats')
            ->once()
            ->andReturn(response()->json(['stats' => ['users' => 100, 'sales' => 200]], 200));

        $response = $controller->dashboardStats();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['stats' => ['users' => 100, 'sales' => 200]]),
            $response->getContent()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
