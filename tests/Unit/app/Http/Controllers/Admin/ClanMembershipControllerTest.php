<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ClanMembershipController;
use App\Http\Requests\ClanMembershipUpdateRequest;
use App\Models\Clan;
use App\Models\ClanMembership;
use App\Models\ClanRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

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

        // Build a request that supplies the new role code using the proper FormRequest class
        $request = ClanMembershipUpdateRequest::create('/', 'POST', ['role' => 'test-role']);

        // Ensure admin routes exist so redirectToRoute() can generate urls
        Route::get('admin/clans/{clan}', fn() => '')->name('admin.clans.show');

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
        // Ensure leader role exists for canDelete() logic
        ClanRole::factory()->create(['code' => 'leader']);
        $role = ClanRole::factory()->create(['code' => 'member']);
        $member = ClanMembership::factory()->for($clan)->create(['clan_role_id' => $role->id]);

        // Ensure admin routes exist for redirects
        Route::get('admin/clans/{clan}', fn() => '')->name('admin.clans.show');
        Route::get('admin/clans', fn() => '')->name('admin.clans.index');

        // Ensure the membership has a user (canDelete() accesses $this->user)
        $member->load('user');
        if (!$member->user) {
            $member->user()->associate(User::factory()->create());
            $member->save();
            $member->refresh();
        }

        $controller = new ClanMembershipController();

        $response = $controller->destroy($clan, $member);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('The clan member has been removed', $response->getSession()->get('successMessage'));
        $this->assertDatabaseMissing('clan_memberships', ['id' => $member->id]);
    }

    public function testDeleteReturnsViewWithCorrectData()
    {
        $clan = Clan::factory()->create();
        // Ensure leader role exists for canDelete() logic
        ClanRole::factory()->create(['code' => 'leader']);
        $member = ClanMembership::factory()->for($clan)->create();

        $controller = new ClanMembershipController();

        // Ensure admin route exists for delete redirect checks (if any)
        Route::get('admin/clans/{clan}', fn() => '')->name('admin.clans.show');

        $response = $controller->delete($clan, $member);

        $this->assertEquals('admin.clanmemberships.delete', $response->name());
        $this->assertArrayHasKey('clan', $response->getData());
        $this->assertArrayHasKey('member', $response->getData());
    }

    public function testDestroyDoesNotRemoveWhenCannotDelete()
    {
        $clan = Clan::factory()->create(['code' => 'test-clan']);
        // Ensure leader role exists for canDelete() logic
        $leaderRole = ClanRole::factory()->create(['code' => 'leader']);
        // create a single leader membership
        $member = ClanMembership::factory()->for($clan)->create(['clan_role_id' => $leaderRole->id]);

        // Ensure admin routes exist for redirects
        Route::get('admin/clans/{clan}', fn() => '')->name('admin.clans.show');

        $controller = new ClanMembershipController();

        $response = $controller->destroy($clan, $member);

        // Should redirect back to admin clans.show with error
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('It is not possible to remove this clan member', $response->getSession()->get('errorMessage'));
        $this->assertDatabaseHas('clan_memberships', ['id' => $member->id]);
    }

    public function testDeleteRedirectsWhenCannotDelete()
    {
        $clan = Clan::factory()->create();
        $leaderRole = ClanRole::factory()->create(['code' => 'leader']);
        // single leader membership
        $member = ClanMembership::factory()->for($clan)->create(['clan_role_id' => $leaderRole->id]);

        $controller = new ClanMembershipController();

        Route::get('admin/clans/{clan}', fn() => '')->name('admin.clans.show');

        $response = $controller->delete($clan, $member);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('It is not possible to remove this clan member', $response->getSession()->get('errorMessage'));
    }
}
