<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\ClanMembershipController;
use Illuminate\Http\Request;
use Mockery;

class ClanMembershipControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new ClanMembershipController();
        $this->assertInstanceOf(ClanMembershipController::class, $controller);
    }

    // FIXME Database connection needed - move to Feature?
    // public function testEditReturnsViewWithCorrectData()
    // {
    //     $clan = Mockery::mock(\App\Models\Clan::class, []);
    //     $member = Mockery::mock(\App\Models\ClanMembership::class, []);

    //     $controller = Mockery::mock(ClanMembershipController::class, [])->makePartial();

    //     $controller->shouldReceive('edit')
    //         ->once()
    //         ->with($clan, $member)
    //         ->andReturn(view('admin.clanmemberships.edit', [
    //             'clan' => $clan,
    //             'member' => $member,
    //         ]));

    //     $response = $controller->edit($clan, $member);

    //     $this->assertEquals('admin.clanmemberships.edit', $response->name());
    //     $this->assertArrayHasKey('clan', $response->getData());
    //     $this->assertArrayHasKey('member', $response->getData());
    // }

    // public function testUpdateRedirectsWithSuccessMessage()
    // {
    //     $request = Mockery::mock(\App\Http\Requests\ClanMembershipUpdateRequest::class);
    //     $clan = Mockery::mock(\App\Models\Clan::class);
    //     $member = Mockery::mock(\App\Models\ClanMembership::class);
    //     $role = Mockery::mock(\App\Models\ClanRole::class);

    //     $request->shouldReceive('input')->with('role')->andReturn('test-role');
    //     $role->shouldReceive('first')->andReturn($role);
    //     $member->shouldReceive('role')->andReturnSelf();
    //     $member->shouldReceive('associate')->with($role);
    //     $member->shouldReceive('save');

    //     $controller = Mockery::mock(ClanMembershipController::class)->makePartial();

    //     $response = $controller->update($request, $clan, $member);

    //     $this->assertEquals(302, $response->getStatusCode());
    //     $this->assertEquals('The clan member has been updated', $response->getSession()->get('successMessage'));
    // }

    // public function testDestroyRedirectsWithSuccessMessage()
    // {
    //     $clan = Mockery::mock(\App\Models\Clan::class);
    //     $member = Mockery::mock(\App\Models\ClanMembership::class);

    //     $member->shouldReceive('canDelete')->andReturn(true);
    //     $member->shouldReceive('delete');

    //     $controller = Mockery::mock(ClanMembershipController::class)->makePartial();

    //     $response = $controller->destroy($clan, $member);

    //     $this->assertEquals(302, $response->getStatusCode());
    //     $this->assertEquals('The clan member has been removed', $response->getSession()->get('successMessage'));
    // }


    // public function testDeleteReturnsViewWithCorrectData()
    // {
    //     $clan = Mockery::mock(\App\Models\Clan::class);
    //     $member = Mockery::mock(\App\Models\ClanMembership::class);

    //     $controller = Mockery::mock(ClanMembershipController::class)->makePartial();

    //     $controller->shouldReceive('delete')
    //         ->once()
    //         ->with($clan, $member)
    //         ->andReturn(view('admin.clanmemberships.delete', [
    //             'clan' => $clan,
    //             'member' => $member,
    //         ]));

    //     $response = $controller->delete($clan, $member);

    //     $this->assertEquals('admin.clanmemberships.delete', $response->name());
    //     $this->assertArrayHasKey('clan', $response->getData());
    //     $this->assertArrayHasKey('member', $response->getData());
    // }
}
