<?php

namespace Tests\Unit\app\Models;

use App\Models\Clan;
use App\Models\ClanMembership;
use App\Models\ClanRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ClanTest extends TestCase
{
    use RefreshDatabase;

    public function testGetRouteKeyNameIsCode()
    {
        $c = new Clan();
        $this->assertEquals('code', $c->getRouteKeyName());
    }

    public function testIsMemberReturnsTrueAndFalse()
    {
        $clan = Clan::factory()->create();
        $user = User::factory()->create();
        $this->assertFalse($clan->isMember($user));

        // add membership via factory
        ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id, 'clan_role_id' => ClanRole::factory()->create()->id]);
        $this->assertTrue($clan->isMember($user));
    }

    public function testAddUserWithStringRoleAndInvalidRole()
    {
        $clan = Clan::factory()->create();
        $user = User::factory()->create();

        // Create role 'member'
        $role = ClanRole::factory()->create(['code' => 'member']);

        $membership = $clan->addUser($user, 'member');
        $this->assertEquals($user->id, $membership->user_id);

        // invalid role should throw
        $this->expectException(InvalidArgumentException::class);
        $clan->addUser(User::factory()->create(), 'does-not-exist');
    }

    public function testAddUserAcceptsRoleObjectAndReturnsExisting()
    {
        $clan = Clan::factory()->create();
        $user = User::factory()->create();

        // Create role and pass the object directly
        $role = ClanRole::factory()->create(['code' => 'captain']);
        $membership = $clan->addUser($user, $role);
        $this->assertEquals($user->id, $membership->user_id);

        // Calling addUser again should return the existing membership (not create duplicate)
        $membership2 = $clan->addUser($user, $role);
        $this->assertEquals($membership->id, $membership2->id);
    }

    public function testGenerateCodeContainsDashAndLength()
    {
        $clan = Clan::factory()->create();
        $code = $clan->generateCode();
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/i', $code);
    }

    public function testProtectedFunctionToStringName()
    {
        $clan = new Clan();
        $clan->name = 'My Clan Name';

        $reflection = new \ReflectionClass($clan);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $result = $method->invoke($clan);

        $this->assertEquals($clan->name, $result);
    }
}
