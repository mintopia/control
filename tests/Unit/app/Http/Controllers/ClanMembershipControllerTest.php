<?php

namespace Tests\Unit\app\Http\Controllers;

use App\Http\Controllers\ClanMembershipController;
use App\Http\Requests\ClanMembershipRequest;
use App\Http\Requests\ClanMembershipUpdateRequest;
use App\Models\Clan;
use App\Models\ClanMembership;
use App\Models\ClanRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ClanMembershipControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Optionally, set up anything needed for all tests
    }

    public function testStoreAddsUserIfNotMember()
    {
        // Ensure the 'member' role exists for default clan membership
        ClanRole::factory()->create(['code' => 'member']);

        $user = User::factory()->withEmailAddress()->create();
        $clan = Clan::factory()->create(['name' => 'TestClan', 'code' => 'abc123', 'invite_code' => 'abc123']);

        $request = new ClanMembershipRequest([
            'code' => 'abc123',
        ]);
        $request->setUserResolver(fn() => $user);

        $controller = new ClanMembershipController();
        $response = $controller->store($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseHas('clan_memberships', [
            'clan_id' => $clan->id,
            'user_id' => $user->id,
        ]);
    }

    public function testStoreDoesNotAddUserIfAlreadyMember()
    {
        // Ensure the 'member' role exists for default clan membership
        ClanRole::factory()->create(['code' => 'member']);

        $user = User::factory()->withEmailAddress()->create();
        $clan = Clan::factory()->create(['name' => 'TestClan', 'code' => 'abc123', 'invite_code' => 'abc123']);
        $clan->addUser($user); // Already a member

        $request = new ClanMembershipRequest([
            'code' => 'abc123',
        ]);
        $request->setUserResolver(fn() => $user);

        $controller = new ClanMembershipController();
        $response = $controller->store($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(1, $clan->members()->where('user_id', $user->id)->count());
    }

    public function testEditReturnsView()
    {
        $clan = Clan::factory()->create();
        $member = ClanMembership::factory()->create(['clan_id' => $clan->id]);
        $controller = new ClanMembershipController();
        $view = $controller->edit($clan, $member);
        $this->assertEquals('clans.members.edit', $view->name());
        $this->assertArrayHasKey('clan', $view->getData());
        $this->assertArrayHasKey('member', $view->getData());
    }

    public function testUpdateAssociatesRoleAndSaves()
    {
        $user = User::factory()->withEmailAddress()->create(['nickname' => 'TestUser']);
        $clan = Clan::factory()->create(['code' => 'abc123']);
        $role = ClanRole::factory()->create(['code' => 'admin']);
        $membership = ClanMembership::factory()->create([
            'clan_id' => $clan->id,
            'user_id' => $user->id,
        ]);

        $request = new ClanMembershipUpdateRequest([
            'role' => 'admin',
        ]);

        $controller = new ClanMembershipController();
        $response = $controller->update($request, $clan, $membership);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('admin', $membership->role->code);
    }

    public function testDestroyRemovesMemberAndRedirects()
    {
        $user = User::factory()->withEmailAddress()->create(['nickname' => 'TestUser']);
        $clan = Clan::factory()->create(['code' => 'abc123']);
        $membership = ClanMembership::factory()->create([
            'clan_id' => $clan->id,
            'user_id' => $user->id,
        ]);

        Auth::shouldReceive('user')->andReturn($user);

        $controller = new ClanMembershipController();
        $response = $controller->destroy($clan, $membership);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseMissing('clan_memberships', [
            'id' => $membership->id,
        ]);
    }

    public function testDeleteReturnsView()
    {
        $user = User::factory()->withEmailAddress()->create();
        $clan = Clan::factory()->create();
        $membership = ClanMembership::factory()->create([
            'clan_id' => $clan->id,
            'user_id' => $user->id,
        ]);

        Auth::shouldReceive('user')->andReturn($user);

        $controller = new ClanMembershipController();
        $view = $controller->delete($clan, $membership);
        $this->assertEquals('clans.members.delete', $view->name());
        $this->assertArrayHasKey('clan', $view->getData());
        $this->assertArrayHasKey('member', $view->getData());
        $this->assertArrayHasKey('leave', $view->getData());
    }
}
