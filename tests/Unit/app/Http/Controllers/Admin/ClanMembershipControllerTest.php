<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\ClanMembershipController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Clan;
use App\Models\ClanMembership;
use App\Models\ClanRole;

class ClanMembershipControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new ClanMembershipController();
        $this->assertInstanceOf(ClanMembershipController::class, $controller);
    }

    public function testEditReturnsViewWithCorrectData()
    {
        $clan = Clan::factory()->create();
        $member = ClanMembership::factory()->for($clan)->create();

        $controller = new ClanMembershipController();

        $response = $controller->edit($clan, $member);

        $this->assertEquals('admin.clanmemberships.edit', $response->name());
        $this->assertArrayHasKey('clan', $response->getData());
        $this->assertArrayHasKey('member', $response->getData());
    }

    public function testUpdateRedirectsWithSuccessMessage()
    {
        $clan = Clan::factory()->create(['code' => 'test-clan']);
        $leaderRole = ClanRole::factory()->create(['code' => 'leader']);
        $role = ClanRole::factory()->create(['code' => 'test-role']);
        $member = ClanMembership::factory()->for($clan)->create(['clan_role_id' => $leaderRole->id]);

        // Build a request that supplies the new role code
        $request = Request::create('/', 'POST', ['role' => 'test-role']);

        $controller = new ClanMembershipController();

        $response = $controller->update($request, $clan, $member);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('The clan member has been updated', $response->getSession()->get('successMessage'));

        // Refresh and assert role changed
        $member->refresh();
        $this->assertEquals($role->id, $member->clan_role_id);
    }

    public function testDestroyRedirectsWithSuccessMessage()
    {
        $clan = Clan::factory()->create(['code' => 'test-clan']);
        $role = ClanRole::factory()->create(['code' => 'member']);
        $member = ClanMembership::factory()->for($clan)->create(['clan_role_id' => $role->id]);

        $controller = new ClanMembershipController();

        $response = $controller->destroy($clan, $member);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('The clan member has been removed', $response->getSession()->get('successMessage'));
        $this->assertDatabaseMissing('clan_memberships', ['id' => $member->id]);
    }

    public function testDeleteReturnsViewWithCorrectData()
    {
        $clan = Clan::factory()->create();
        $member = ClanMembership::factory()->for($clan)->create();

        $controller = new ClanMembershipController();

        $response = $controller->delete($clan, $member);

        $this->assertEquals('admin.clanmemberships.delete', $response->name());
        $this->assertArrayHasKey('clan', $response->getData());
        $this->assertArrayHasKey('member', $response->getData());
    }
}
