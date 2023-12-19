<?php

namespace App\Policies;

use App\Models\ClanMembership;
use App\Models\ClanRole;
use App\Models\User;

class ClanMembershipPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function delete(User $user, ClanMembership $clanMembership): bool
    {
        return $clanMembership->canDelete($user);
    }

    public function update(User $user, ClanMembership $clanMembership): bool
    {
        $leaderRole = ClanRole::whereCode('leader')->first();
        if ($user->id === $clanMembership->user_id) {
            if ($clanMembership->clan_role_id !== $leaderRole->id) {
                return false;
            }
            $count = $clanMembership->clan->members()->where('clan_role_id', $leaderRole->id)->count();
            return $count > 1;
        }
        $count = $clanMembership->clan->members()->where('clan_role_id', $leaderRole->id)->whereUserId($user->id)->count();
        return $count > 0;
    }
}
