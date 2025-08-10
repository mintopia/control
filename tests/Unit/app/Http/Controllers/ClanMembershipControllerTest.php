<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use Illuminate\Foundation\Testing\RefreshDatabase;

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
        \App\Models\ClanRole::factory()->create(['code' => 'member']);

        $user = \App\Models\User::factory()->withEmailAddress()->create();
        $clan = \App\Models\Clan::factory()->create(['name' => 'TestClan', 'code' => 'abc123', 'invite_code' => 'abc123']);

        $request = new \App\Http\Requests\ClanMembershipRequest([
            'code' => 'abc123',
        ]);
        $request->setUserResolver(fn() => $user);

        $controller = new \App\Http\Controllers\ClanMembershipController();
        $response = $controller->store($request);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertDatabaseHas('clan_memberships', [
            'clan_id' => $clan->id,
            'user_id' => $user->id,
        ]);
    }

    public function testStoreDoesNotAddUserIfAlreadyMember()
    {
        // Ensure the 'member' role exists for default clan membership
        \App\Models\ClanRole::factory()->create(['code' => 'member']);

        $user = \App\Models\User::factory()->withEmailAddress()->create();
        $clan = \App\Models\Clan::factory()->create(['name' => 'TestClan', 'code' => 'abc123', 'invite_code' => 'abc123']);
        $clan->addUser($user); // Already a member

        $request = new \App\Http\Requests\ClanMembershipRequest([
            'code' => 'abc123',
        ]);
        $request->setUserResolver(fn() => $user);

        $controller = new \App\Http\Controllers\ClanMembershipController();
        $response = $controller->store($request);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals(1, $clan->members()->where('user_id', $user->id)->count());
    }

    public function testEditReturnsView()
    {
        $clan = \App\Models\Clan::factory()->create();
        $member = \App\Models\ClanMembership::factory()->create(['clan_id' => $clan->id]);
        $controller = new \App\Http\Controllers\ClanMembershipController();
        $view = $controller->edit($clan, $member);
        $this->assertEquals('clans.members.edit', $view->name());
        $this->assertArrayHasKey('clan', $view->getData());
        $this->assertArrayHasKey('member', $view->getData());
    }

    public function testUpdateAssociatesRoleAndSaves()
    {
        $user = \App\Models\User::factory()->withEmailAddress()->create(['nickname' => 'TestUser']);
        $clan = \App\Models\Clan::factory()->create(['code' => 'abc123']);
        $role = \App\Models\ClanRole::factory()->create(['code' => 'admin']);
        $membership = \App\Models\ClanMembership::factory()->create([
            'clan_id' => $clan->id,
            'user_id' => $user->id,
        ]);

        $request = new \App\Http\Requests\ClanMembershipUpdateRequest([
            'role' => 'admin',
        ]);

        $controller = new \App\Http\Controllers\ClanMembershipController();
        $response = $controller->update($request, $clan, $membership);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals('admin', $membership->role->code);
    }

    public function testDestroyRemovesMemberAndRedirects()
    {
        $user = \App\Models\User::factory()->withEmailAddress()->create(['nickname' => 'TestUser']);
        $clan = \App\Models\Clan::factory()->create(['code' => 'abc123']);
        $membership = \App\Models\ClanMembership::factory()->create([
            'clan_id' => $clan->id,
            'user_id' => $user->id,
        ]);

        Auth::shouldReceive('user')->andReturn($user);

        $controller = new \App\Http\Controllers\ClanMembershipController();
        $response = $controller->destroy($clan, $membership);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertDatabaseMissing('clan_memberships', [
            'id' => $membership->id,
        ]);
    }

    public function testDeleteReturnsView()
    {
        $user = \App\Models\User::factory()->withEmailAddress()->create();
        $clan = \App\Models\Clan::factory()->create();
        $membership = \App\Models\ClanMembership::factory()->create([
            'clan_id' => $clan->id,
            'user_id' => $user->id,
        ]);

        Auth::shouldReceive('user')->andReturn($user);

        $controller = new \App\Http\Controllers\ClanMembershipController();
        $view = $controller->delete($clan, $membership);
        $this->assertEquals('clans.members.delete', $view->name());
        $this->assertArrayHasKey('clan', $view->getData());
        $this->assertArrayHasKey('member', $view->getData());
        $this->assertArrayHasKey('leave', $view->getData());
    }
}
