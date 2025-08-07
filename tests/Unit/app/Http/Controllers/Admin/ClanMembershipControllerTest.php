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

    public function testAddMemberReturnsSuccessResponse()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn(['member_id' => 1, 'clan_id' => 2]);

        $controller = Mockery::mock(ClanMembershipController::class)->makePartial();

        $controller->shouldReceive('addMember')
            ->once()
            ->with(Mockery::type(Request::class))
            ->andReturn(response()->json(['message' => 'Member added'], 200));

        $response = $controller->addMember($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Member added']),
            $response->getContent()
        );
    }

    public function testRemoveMemberReturnsSuccessResponse()
    {
        $controller = Mockery::mock(ClanMembershipController::class)->makePartial();

        $controller->shouldReceive('removeMember')
            ->once()
            ->with(1, 2)
            ->andReturn(response()->json(['message' => 'Member removed'], 200));

        $response = $controller->removeMember(1, 2);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Member removed']),
            $response->getContent()
        );
    }

    public function testListMembersReturnsExpectedResponse()
    {
        $controller = Mockery::mock(ClanMembershipController::class)->makePartial();

        $controller->shouldReceive('listMembers')
            ->once()
            ->with(2)
            ->andReturn(response()->json(['members' => ['member1', 'member2']], 200));

        $response = $controller->listMembers(2);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['members' => ['member1', 'member2']]),
            $response->getContent()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
