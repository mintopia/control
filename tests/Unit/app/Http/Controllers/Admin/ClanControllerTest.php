<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\ClanController;
use Illuminate\Http\Request;
use App\Models\Clan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Requests\ClanRequest;
use App\Http\Requests\Admin\DeleteRequest;
use Illuminate\Contracts\View\View;

class ClanControllerTest extends TestCase
{
    use RefreshDatabase;
    public function testCanInstantiateController()
    {
        $controller = new ClanController();
        $this->assertInstanceOf(ClanController::class, $controller);
    }

    public function testIndexReturnsExpectedResponse()
    {
        // Basic smoke test: index should return a view response when user has no clans
        $user = User::factory()->create();
        $request = Request::create('/admin/clans', 'GET');
        $request->setUserResolver(fn() => $user);

        $controller = new ClanController();
        $view = $controller->index($request);

        // index returns a view and contains 'clans' key
        $this->assertInstanceOf(View::class, $view);
        $this->assertArrayHasKey('clans', $view->getData());
    }

    public function testStoreValidatesAndSavesData()
    {
        $user = User::factory()->create();
        // Ensure the 'leader' role exists so addUser() can resolve it
        \App\Models\ClanRole::factory()->create(['code' => 'leader', 'name' => 'Leader']);
        $request = ClanRequest::create('/admin/clans', 'POST', ['name' => 'Test Clan']);
        $request->setUserResolver(fn() => $user);

        // The store action lives on the non-admin controller
        $controller = new \App\Http\Controllers\ClanController();
        $response = $controller->store($request);

        $this->assertDatabaseHas('clans', ['name' => 'Test Clan']);
    }

    public function testDestroyDeletesClan()
    {
        $clan = Clan::factory()->create();
        $request = DeleteRequest::create('/admin/clans/' . $clan->id, 'DELETE', ['confirm' => 'delete']);
        $request->setUserResolver(fn() => User::factory()->create());

        $controller = new ClanController();
        $response = $controller->destroy($request, $clan);

        $this->assertDatabaseMissing('clans', ['id' => $clan->id]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
