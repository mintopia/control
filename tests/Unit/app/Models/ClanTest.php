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

    public function test_get_route_key_name_is_code()
    {
        $c = new Clan();
        $this->assertEquals('code', $c->getRouteKeyName());
    }

    public function test_is_member_returns_true_and_false()
    {
        $clan = Clan::factory()->create();
        $user = User::factory()->create();
        $this->assertFalse($clan->isMember($user));

        // add membership via factory
        ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id, 'clan_role_id' => ClanRole::factory()->create()->id]);
        $this->assertTrue($clan->isMember($user));
    }

    public function test_add_user_with_string_role_and_invalid_role()
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

    public function test_add_user_accepts_role_object_and_returns_existing()
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

    public function test_generate_code_contains_dash_and_length()
    {
        $clan = Clan::factory()->create();
        $code = $clan->generateCode();
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/i', $code);
    }

    public function test_to_string_name_via_dummy_exposes_name()
    {
        // Use a tiny dummy subclass to expose the protected toStringName method
        $dummy = new HelperClasses\DummyClan();
        $dummy->name = 'My Clan Name';
        $this->assertEquals('My Clan Name', $dummy->exposeToString());
    }
}

// Small helper class inside this test file to expose protected toStringName()
