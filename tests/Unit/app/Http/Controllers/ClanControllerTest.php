<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\ClanController;
use Illuminate\Http\Request;
use App\Models\Clan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Requests\ClanRequest;
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
        $user = User::factory()->create();
        $request = Request::create('/clans', 'GET');
        $request->setUserResolver(fn() => $user);

        $controller = new ClanController();
        $view = $controller->index($request);

        $this->assertInstanceOf(View::class, $view);
        // Non-admin controller returns 'members' in the view data
        $this->assertArrayHasKey('members', $view->getData());
    }

    public function testStoreValidatesAndSavesData()
    {
        $user = User::factory()->create();
        // Ensure the 'leader' role exists so addUser() can resolve it
        \App\Models\ClanRole::factory()->create(['code' => 'leader', 'name' => 'Leader']);
        $request = ClanRequest::create('/clans', 'POST', ['name' => 'Test Clan']);
        $request->setUserResolver(fn() => $user);

        $controller = new ClanController();
        $response = $controller->store($request);

        $this->assertDatabaseHas('clans', ['name' => 'Test Clan']);
    }

    public function testDestroyDeletesClan()
    {
        $clan = Clan::factory()->create();
        $controller = new ClanController();
        $response = $controller->destroy($clan);

        $this->assertDatabaseMissing('clans', ['id' => $clan->id]);
    }
}
