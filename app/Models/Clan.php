<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

use function App\makeCode;

/**
 * App\Models\Clan
 *
 * @mixin IdeHelperClan
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $invite_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ClanMembership> $members
 * @property-read int|null $members_count
 * @method static Builder|Clan newModelQuery()
 * @method static Builder|Clan newQuery()
 * @method static Builder|Clan query()
 * @method static Builder|Clan whereCode($value)
 * @method static Builder|Clan whereCreatedAt($value)
 * @method static Builder|Clan whereId($value)
 * @method static Builder|Clan whereInviteCode($value)
 * @method static Builder|Clan whereName($value)
 * @method static Builder|Clan whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Clan extends Model
{
    use HasFactory;
    use ToString;

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
