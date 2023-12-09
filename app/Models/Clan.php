<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use function App\makeCode;

/**
 * App\Models\Clan
 *
 * @property int $id
 * @property string $name
 * @property string $invite_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClanMembership> $members
 * @property-read int|null $members_count
 * @method static \Illuminate\Database\Eloquent\Builder|Clan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Clan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Clan query()
 * @method static \Illuminate\Database\Eloquent\Builder|Clan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clan whereInviteCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clan whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @property string $code
 * @method static \Illuminate\Database\Eloquent\Builder|Clan whereCode($value)
 * @mixin \Eloquent
 */
class Clan extends Model
{
    use HasFactory, ToString;

    public function getRouteKeyName()
    {
        return 'code';
    }

    protected function toStringName(): string
    {
        return $this->name;
    }

    public function members(): HasMany
    {
        return $this->hasMany(ClanMembership::class);
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->count() > 0;
    }

    public function addUser(User $user, string|ClanRole $role = 'member'): ClanMembership
    {
        if (is_string($role)) {
            $role = ClanRole::whereCode($role)->first();
            if (!$role) {
                throw new \InvalidArgumentException('Unable to find role');
            }
        }
        $member = $this->members()->where('user_id', $user->id)->first();
        if (!$member) {
            $member = new ClanMembership();
            $member->user()->associate($user);
            $member->clan()->associate($this);
            $member->role()->associate($role);
            $member->save();
        }
        return $member;
    }

    public function generateCode(): string
    {
        $this->invite_code = makeCode(4) . '-' . makeCode(4);
        return $this->invite_code;
    }
}
