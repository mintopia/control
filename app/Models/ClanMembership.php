<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ClanMembership
 *
 * @property int $id
 * @property int $user_id
 * @property int $clan_id
 * @property int $clan_role_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Clan $clan
 * @property-read \App\Models\ClanRole|null $role
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
    use HasFactory, ToString;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ClanRole::class);
    }

    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clan::class);
    }
}
