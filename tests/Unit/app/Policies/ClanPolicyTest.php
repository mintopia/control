<?php

namespace Tests\Unit\app\Policies;

use Tests\TestCase;
use App\Policies\ClanPolicy;
use App\Models\Clan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClanPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function testViewReturnsTrueIfUserIsMember()
    {
        $user = User::factory()->create();
        $clan = Clan::factory()->create();
        \App\Models\ClanMembership::factory()->create([
            'user_id' => $user->id,
            'clan_id' => $clan->id,
        ]);

        $policy = new ClanPolicy();
        $this->assertTrue($policy->view($user, $clan));
    }

    public function testViewReturnsFalseIfUserIsNotMember()
    {
        $user = User::factory()->create();
        $clan = Clan::factory()->create();

        $policy = new ClanPolicy();
        $this->assertFalse($policy->view($user, $clan));
    }
}
