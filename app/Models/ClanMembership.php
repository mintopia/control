<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperClanMembership
 */
class ClanMembership extends Model
{
    use HasFactory, ToString;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ClanRole::class, 'clan_role_id');
    }

    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clan::class);
    }

    public function canDelete(?User $user = null): bool
    {
        if (!$user) {
            $user = $this->user;
        }
        $leaderRole = ClanRole::whereCode('leader')->first();
        if ($user->id === $this->user_id) {
            if ($this->clan_role_id === $leaderRole->id) {
                // We're a leader - only allow delete if there is another leader
                $leaderCount = $this->clan->members()->whereHas('role', function ($query) {
                    $query->whereCode('leader');
                })->count();
                if ($leaderCount > 1) {
                    return true;
                }
                return false;
            } else {
                // We're not leader, it's fine
                return true;
            }
        } else {
            $clanLeader = ClanMembership::whereClanId($this->clan_id)->whereUserId($user->id)->where('clan_role_id', $leaderRole->id)->count();
            return $clanLeader > 0;
        }
    }
}
