<?php

namespace App\Policies;

use App\Models\Clan;
use App\Models\ClanMembership;
use App\Models\User;

class ClanPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Clan $clan): bool
    {
        return $clan->members()->where('user_id', $user->id)->count() === 1;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Clan $clan): bool
    {
        $isLeader = (bool)ClanMembership::whereUserId($user->id)->whereClanId($clan->id)->whereHas('role', function ($query) {
            $query->whereCode('leader');
        })->count();
        return $isLeader;
    }
}
