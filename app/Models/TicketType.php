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
 * App\Models\TicketType
 *
 * @mixin IdeHelperTicketType
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property int $has_seat
 * @property string|null $discord_role_id
 * @property string|null $discord_role_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TicketTypeMapping> $mappings
 * @property-read int|null $mappings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType query()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereDiscordRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereDiscordRoleName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereHasSeat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TicketType extends Model
{
    use HasFactory;
    use ToString;

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
