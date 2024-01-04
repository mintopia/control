<?php

namespace App\Models;

use App\Models\Traits\ToString;
use App\Services\DiscordApi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Artisan;

/**
 * @mixin IdeHelperTicketType
 */
class TicketType extends Model
{
    use HasFactory, ToString;

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(TicketTypeMapping::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function updateDiscordRoleName(): void
    {
        if (!$this->discord_role_id) {
            $this->discord_role_name = null;
            return;
        }

        $api = resolve(DiscordApi::class);
        if (!$api) {
            return;
        }

        $roles = $api->getRoles();
        if (isset($roles[$this->discord_role_id])) {
            $this->discord_role_name = $roles[$this->discord_role_id];
        } else {
            $this->discord_role_name = null;
            $this->discord_role_id = null;
        }
    }

    public function syncDiscordRoles(): void
    {
        Artisan::queue('control:sync-discord-roles');
    }
}
