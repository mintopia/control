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

/**
 * App\Models\ClanRole
 *
 * @mixin IdeHelperClanRole
 * @property int $id
 * @property string $code
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ClanMembership> $members
 * @property-read int|null $members_count
 * @method static Builder|ClanRole newModelQuery()
 * @method static Builder|ClanRole newQuery()
 * @method static Builder|ClanRole query()
 * @method static Builder|ClanRole whereCode($value)
 * @method static Builder|ClanRole whereCreatedAt($value)
 * @method static Builder|ClanRole whereId($value)
 * @method static Builder|ClanRole whereName($value)
 * @method static Builder|ClanRole whereUpdatedAt($value)
 * @mixin Eloquent
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
