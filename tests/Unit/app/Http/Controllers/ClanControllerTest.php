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

    public function testIndexWithDescOrder()
    {
        $user = User::factory()->create();
        $request = Request::create('/clans', 'GET', ['order_direction' => 'desc']);
        $request->setUserResolver(fn() => $user);

        $controller = new ClanController();
        $view = $controller->index($request);

        $this->assertInstanceOf(View::class, $view);
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

    public function testCreateReturnsView()
    {
        $controller = new ClanController();
        $view = $controller->create();
        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $view);
    }

    public function testShowReturnsViewWithMembers()
    {
        $clan = Clan::factory()->create();
        $user = User::factory()->create();
        // create membership so show() has something to paginate
        \Database\Factories\ClanMembershipFactory::new()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);

        $request = Request::create('/clans/' . $clan->code, 'GET');
        $controller = new ClanController();
        $view = $controller->show($request, $clan);
        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $view);
        $this->assertArrayHasKey('members', $view->getData());
    }

    public function testShowOrdersByNameDesc()
    {
        $clan = Clan::factory()->create();
        // create two users with nicknames to check ordering
        $userA = User::factory()->create(['nickname' => 'aaa']);
        $userB = User::factory()->create(['nickname' => 'zzz']);

        // create memberships so show() has something to paginate
        \Database\Factories\ClanMembershipFactory::new()->create(['clan_id' => $clan->id, 'user_id' => $userA->id]);
        \Database\Factories\ClanMembershipFactory::new()->create(['clan_id' => $clan->id, 'user_id' => $userB->id]);

        $request = Request::create('/clans/' . $clan->code, 'GET', ['order' => 'name', 'order_direction' => 'desc']);
        $request->setUserResolver(fn() => $userB);

        $controller = new ClanController();
        $view = $controller->show($request, $clan);

        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $view);
        $members = $view->getData()['members']->items();
        $this->assertGreaterThanOrEqual(2, count($members));
        // first member should have nickname 'zzz' due to desc ordering
        $this->assertEquals('zzz', $members[0]->user->nickname);
    }

    public function testEditReturnsView()
    {
        $clan = Clan::factory()->create();
        $controller = new ClanController();
        $view = $controller->edit($clan);
        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $view);
        $this->assertArrayHasKey('clan', $view->getData());
    }

    public function testUpdatePersistsChanges()
    {
        $clan = Clan::factory()->create(['name' => 'Old']);
        $request = ClanRequest::create('/clans/' . $clan->id, 'PUT', ['name' => 'New Name']);
        $controller = new ClanController();
        $response = $controller->update($request, $clan);
        $this->assertDatabaseHas('clans', ['id' => $clan->id, 'name' => 'New Name']);
    }

    public function testRegenerateChangesInviteCode()
    {
        $clan = Clan::factory()->create(['invite_code' => 'AAAA-BBBB']);
        $controller = new ClanController();
        $response = $controller->regenerate($clan);
        $this->assertNotEquals('AAAA-BBBB', $clan->fresh()->invite_code);
    }

    public function testDeleteReturnsView()
    {
        $clan = Clan::factory()->create();
        $controller = new ClanController();
        $view = $controller->delete($clan);
        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $view);
        $this->assertArrayHasKey('clan', $view->getData());
    }
}
