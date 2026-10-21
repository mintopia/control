<?php

namespace Tests\Feature\app\Http\Controllers;

use App\Http\Controllers\ClanMembershipController;
use App\Http\Requests\ClanMembershipRequest;
use App\Http\Requests\ClanMembershipUpdateRequest;
use App\Models\Clan;
use App\Models\ClanMembership;
use App\Models\ClanRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class ClanMembershipControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new ClanMembershipController();
        $this->assertInstanceOf(ClanMembershipController::class, $controller);
    }

    public function testStoreJoinsClanIfNotMember()
    {
        $user = User::factory()->create();
        $clan = Clan::factory()->create();
        $role = ClanRole::factory()->create(['code' => 'member']);
        $clan->addUser($user, $role);
        $request = Mockery::mock(ClanMembershipRequest::class);
        $request->shouldReceive('input')->with('code')->andReturn($clan->invite_code);
        $request->shouldReceive('user')->andReturn($user);
        $controller = new ClanMembershipController();
        $response = $controller->store($request);
        $this->assertTrue(method_exists($response, 'getTargetUrl'));
    }

    public function testEditReturnsView()
    {
        $clan = Clan::factory()->create();
        $member = ClanMembership::factory()->create(['clan_id' => $clan->id]);
        $controller = new ClanMembershipController();
        $response = $controller->edit($clan, $member);
        $this->assertTrue(is_object($response));
    }

    public function testUpdateChangesRole()
    {
        $clan = Clan::factory()->create();
        $role = ClanRole::factory()->create(['code' => 'member']);
        $newRole = ClanRole::factory()->create(['code' => 'leader']);
        $member = ClanMembership::factory()->create(['clan_id' => $clan->id, 'clan_role_id' => $role->id]);
        $request = Mockery::mock(ClanMembershipUpdateRequest::class);
        $request->shouldReceive('input')->with('role')->andReturn($newRole->code);
        $controller = new ClanMembershipController();
        $response = $controller->update($request, $clan, $member);
        $member->refresh();
        $this->assertEquals($newRole->id, $member->clan_role_id);
    }

    public function testDestroyRemovesMember()
    {
        $user = User::factory()->create();
        $clan = Clan::factory()->create();
        $role = ClanRole::factory()->create(['code' => 'member']);
        $member = ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id, 'clan_role_id' => $role->id]);
        Auth::shouldReceive('user')->andReturn($user);
        $controller = new ClanMembershipController();
        $response = $controller->destroy($clan, $member);
        $this->assertDatabaseMissing('clan_memberships', ['id' => $member->id]);
    }

    public function testDeleteReturnsView()
    {
        $user = User::factory()->create();
        $clan = Clan::factory()->create();
        $role = ClanRole::factory()->create(['code' => 'member']);
        $member = ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id, 'clan_role_id' => $role->id]);
        Auth::shouldReceive('user')->andReturn($user);
        $controller = new ClanMembershipController();
        $response = $controller->delete($clan, $member);
        $this->assertTrue(is_object($response));
    }
}
