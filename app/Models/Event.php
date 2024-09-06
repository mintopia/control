<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Event
 *
 * @mixin IdeHelperEvent
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int $draft
 * @property string|null $boxoffice_url
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property int $seating_locked
 * @property \Illuminate\Support\Carbon|null $seating_opens_at
 * @property \Illuminate\Support\Carbon|null $seating_closes_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventMapping> $mappings
 * @property-read int|null $mappings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SeatGroup> $seatGroups
 * @property-read int|null $seat_groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SeatingPlan> $seatingPlans
 * @property-read int|null $seating_plans_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TicketType> $ticketTypes
 * @property-read int|null $ticket_types_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @method static \Illuminate\Database\Eloquent\Builder|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereBoxofficeUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereDraft($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereSeatingClosesAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereSeatingLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereSeatingOpensAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Event extends Model
{
    use HasFactory, ToString;

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'seating_opens_at' => 'datetime',
        'seating_closes_at' => 'datetime',
    ];

    public function getRouteKeyName()
    {
        return 'code';
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(EventMapping::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function seatingPlans(): HasMany
    {
        return $this->hasMany(SeatingPlan::class);
    }

    public function seatGroups(): HasMany
    {
        return $this->hasMany(SeatGroup::class);
    }

    public function getAvailableEventMappings(?EventMapping $existing = null): array
    {
        $allProviders = TicketProvider::all();
        $result = [];
        foreach ($allProviders as $provider) {
            $events = array_filter($provider->getEvents(), function ($event) use ($existing, $provider) {
                if (!$event->used) {
                    return true;
                }
                if ($existing && $existing->ticket_provider_id === $provider->id && $existing->external_id == $event->id) {
                    return true;
                }
                return false;
            });
            if ($events) {
                $result[] = (object)[
                    'provider' => $provider,
                    'events' => array_values($events),
                ];
            }
        }
        return $result;
    }

    public function getAvailableTicketMappings(?TicketTypeMapping $existing = null): array
    {
        $allProviders = TicketProvider::all();
        $result = [];
        foreach ($allProviders as $provider) {
            $types = array_filter($provider->getTicketTypes($this), function ($type) use ($existing, $provider) {
                if (!$type->used) {
                    return true;
                }
                if ($existing && $existing->ticket_provider_id == $provider->id && $existing->external_id == $type->id) {
                    return true;
                }
                return false;
            });
            if ($types) {
                $result[] = (object)[
                    'provider' => $provider,
                    'types' => array_values($types),
                ];
            }
        }
        return $result;
    }

    protected function toStringName(): string
    {
        return $this->code;
    }
}
