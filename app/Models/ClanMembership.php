<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ClanMembership
 *
 * @mixin IdeHelperClanMembership
 * @property int $id
 * @property int $user_id
 * @property int $clan_id
 * @property int $clan_role_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Clan $clan
 * @property-read \App\Models\ClanRole $role
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership whereClanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership whereClanRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanMembership whereUserId($value)
 * @mixin \Eloquent
 */
class ClanMembership extends Model
{
    use HasFactory;
    use ToString;

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
