<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use function App\makeCode;

/**
 * @mixin IdeHelperClan
 */
class Clan extends Model
{
    use HasFactory, ToString;

    public function getRouteKeyName()
    {
        return 'code';
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->count() > 0;
    }

    public function members(): HasMany
    {
        return $this->hasMany(ClanMembership::class);
    }

    public function addUser(User $user, string|ClanRole $role = 'member'): ClanMembership
    {
        if (is_string($role)) {
            $role = ClanRole::whereCode($role)->first();
            if (!$role) {
                throw new InvalidArgumentException('Unable to find role');
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

    protected function toStringName(): string
    {
        return $this->name;
    }
}
