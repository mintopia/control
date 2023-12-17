<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * App\Models\ClanRole
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClanMembership> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperClanRole
 */
class ClanRole extends Model
{
    use HasFactory, ToString;

    protected function toStringName(): string
    {
        return $this->code;
    }

    public function members(): HasMany
    {
        return $this->hasMany(ClanMembership::class);
    }
}
