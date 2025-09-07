<?php

namespace App\Models;

use App\Models\Traits\ToString;
use App\Services\DiscordApi;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Event $event
 * @property-read Collection<int, TicketTypeMapping> $mappings
 * @property-read int|null $mappings_count
 * @property-read Collection<int, Ticket> $tickets
 * @property-read int|null $tickets_count
 * @method static Builder|TicketType newModelQuery()
 * @method static Builder|TicketType newQuery()
 * @method static Builder|TicketType query()
 * @method static Builder|TicketType whereCreatedAt($value)
 * @method static Builder|TicketType whereDiscordRoleId($value)
 * @method static Builder|TicketType whereDiscordRoleName($value)
 * @method static Builder|TicketType whereEventId($value)
 * @method static Builder|TicketType whereHasSeat($value)
 * @method static Builder|TicketType whereId($value)
 * @method static Builder|TicketType whereName($value)
 * @method static Builder|TicketType whereUpdatedAt($value)
 * @mixin Eloquent
 */
class TicketType extends Model
{
    use HasFactory;
    use ToString;

    protected $fillable = [
        'event_id',
        'name',
        'has_seat',
        'discord_role_id',
        'discord_role_name',
    ];

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
