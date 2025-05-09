<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\ClanRole
 *
 * @mixin IdeHelperClanRole
 * @property int $id
 * @property string $code
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClanMembership> $members
 * @property-read int|null $members_count
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClanRole whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ClanRole extends Model
{
    use HasFactory;
    use ToString;

    public function members(): HasMany
    {
        return $this->hasMany(ClanMembership::class);
    }

    protected function toStringName(): string
    {
        return $this->code;
    }
}
