<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ClanMembership
 *
 * @mixin IdeHelperClanMembership
 * @property int $id
 * @property int $user_id
 * @property int $clan_id
 * @property int $clan_role_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Clan $clan
 * @property-read ClanRole $role
 * @property-read User $user
 * @method static Builder|ClanMembership newModelQuery()
 * @method static Builder|ClanMembership newQuery()
 * @method static Builder|ClanMembership query()
 * @method static Builder|ClanMembership whereClanId($value)
 * @method static Builder|ClanMembership whereClanRoleId($value)
 * @method static Builder|ClanMembership whereCreatedAt($value)
 * @method static Builder|ClanMembership whereId($value)
 * @method static Builder|ClanMembership whereUpdatedAt($value)
 * @method static Builder|ClanMembership whereUserId($value)
 * @mixin Eloquent
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
