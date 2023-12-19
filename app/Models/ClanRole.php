<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperClanRole
 */
class ClanRole extends Model
{
    use HasFactory, ToString;

    public function members(): HasMany
    {
        return $this->hasMany(ClanMembership::class);
    }

    protected function toStringName(): string
    {
        return $this->code;
    }
}
