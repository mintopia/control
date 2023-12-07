<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
 * @mixin \Eloquent
 */
class Clan extends Model
{
    use HasFactory, ToString;

    protected function toStringName(): string
    {
        return $this->name;
    }

    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, ClanMembership::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ClanMembership::class);
    }
}
