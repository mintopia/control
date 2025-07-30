<?php

namespace Database\Factories;

use App\Models\ClanMembership;
use App\Models\User;
use App\Models\Clan;
use App\Models\ClanRole;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClanMembershipFactory extends Factory
{
    protected $model = ClanMembership::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'clan_id' => Clan::factory(),
            'clan_role_id' => ClanRole::factory(),
        ];
    }
}
