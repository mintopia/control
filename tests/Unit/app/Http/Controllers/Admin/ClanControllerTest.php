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

    public function testIndexFiltersAndInviteCodeOrderDesc()
    {
        $c1 = Clan::factory()->create(['name' => 'Alpha', 'code' => 'A1', 'invite_code' => 'AAA']);
        $c2 = Clan::factory()->create(['name' => 'Beta', 'code' => 'B1', 'invite_code' => 'BBB']);

        $controller = new ClanController();

        // filter by invite_code partial
        $resp = $controller->index(Request::create('/admin/clans', 'GET', ['invite_code' => 'AA']));
        $items = $resp->getData()['clans']->items();
        $this->assertGreaterThanOrEqual(1, count($items));

        // order by invite_code desc
        $resp = $controller->index(Request::create('/admin/clans', 'GET', ['order' => 'invite_code', 'order_direction' => 'desc']));
        $items = $resp->getData()['clans']->items();
        $this->assertGreaterThanOrEqual(2, count($items));
        $this->assertEquals($c2->id, $items[0]->id);
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

    public function testShowReturnsMembersAndParams()
    {
        $clan = Clan::factory()->create();
        $userA = User::factory()->create(['nickname' => 'aaa']);
        $userB = User::factory()->create(['nickname' => 'zzz']);

        // create memberships
        \Database\Factories\ClanMembershipFactory::new()->create(['clan_id' => $clan->id, 'user_id' => $userA->id]);
        \Database\Factories\ClanMembershipFactory::new()->create(['clan_id' => $clan->id, 'user_id' => $userB->id]);

        $request = Request::create('/admin/clans/' . $clan->id, 'GET', ['order' => 'user', 'order_direction' => 'desc']);
        $controller = new ClanController();
        $view = $controller->show($request, $clan);

        $this->assertInstanceOf(View::class, $view);
        $data = $view->getData();
        $this->assertArrayHasKey('members', $data);
        $members = $data['members'];
        $this->assertGreaterThanOrEqual(2, $members->count());
        // first member should be userB with nickname 'zzz'
        $this->assertEquals('zzz', $members->first()->user->nickname);
    }

    public function testEditReturnsView()
    {
        $clan = Clan::factory()->create();
        $controller = new ClanController();
        $view = $controller->edit($clan);
        $this->assertInstanceOf(View::class, $view);
        $this->assertArrayHasKey('clan', $view->getData());
    }

    public function testUpdatePersistsChanges()
    {
        $clan = Clan::factory()->create(['name' => 'OldName']);
        $request = ClanRequest::create('/admin/clans/' . $clan->id, 'POST', ['name' => 'NewName']);
        $controller = new ClanController();
        $resp = $controller->update($request, $clan);
        $this->assertDatabaseHas('clans', ['id' => $clan->id, 'name' => 'NewName']);
    }

    public function testDeleteReturnsViewForAdmin()
    {
        $clan = Clan::factory()->create();
        $controller = new ClanController();
        $view = $controller->delete($clan);
        $this->assertInstanceOf(View::class, $view);
        $this->assertArrayHasKey('clan', $view->getData());
    }

    public function testRegenerateChangesInviteCode()
    {
        $clan = Clan::factory()->create(['invite_code' => 'AAAA-BBBB']);
        $controller = new ClanController();
        $resp = $controller->regenerate($clan);
        $this->assertNotEquals('AAAA-BBBB', $clan->fresh()->invite_code);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
