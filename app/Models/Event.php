<?php

namespace App\Models;

use App\Models\EventMapping;
use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
 * @property bool $seating_locked
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
    use HasFactory;
    use ToString;

    protected $fillable = ['name', 'code', 'draft', 'boxoffice_url', 'starts_at', 'ends_at', 'seating_locked', 'seating_opens_at', 'seating_closes_at'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'seating_opens_at' => 'datetime',
        'seating_closes_at' => 'datetime',
        'seating_locked' => 'boolean',
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

    // CHECK updated functions to allow testing - needs validation
    /*
    public function getAvailableEventMappings(?EventMapping $existing)
    {
        $providers = TicketProvider::where('enabled', 1)->get();
        $results = [];
        foreach ($providers as $provider) {
            $impl = app()->make($provider->provider_class, ['provider' => $provider]);
            $events = $impl->getEvents();
            foreach ($events as $id => $data) {
                // include used events only if they match the provided existing mapping
                if ($data->used) {
                    if (! $existing || $existing->ticket_provider_id !== $provider->id || (string)$existing->external_id !== (string)$id) {
                        continue;
                    }
                }
                // ensure not already mapped unless it's the existing mapping itself
                $existsQuery = EventMapping::where('ticket_provider_id', $provider->id)
                    ->where('external_id', $id);
                $exists = $existsQuery->exists();
                if ($exists) {
                    // if existing mapping is provided and it's the same mapping, allow it
                    if (! $existing || $existing->ticket_provider_id !== $provider->id || (string)$existing->external_id !== (string)$id) {
                        continue;
                    }
                }
                $results[] = (object)[
                    'provider' => $provider,
                    'events' => [$data]
                ];
            }
        }
        return $results;
    }

    public function getAvailableTicketMappings(?EventMapping $existing)
    {
        $providers = TicketProvider::where('enabled', 1)->get();
        $results = [];
        foreach ($providers as $provider) {
            $impl = app()->make($provider->provider_class, ['provider' => $provider]);
            // find provider event for this event
            $providerEvent = EventMapping::where('ticket_provider_id', $provider->id)
                ->where('event_id', $this->id)
                ->first();
            if (!$providerEvent) {
                continue;
            }
            $types = $impl->getTicketTypes($providerEvent->external_id);
            foreach ($types as $id => $type) {
                // include used types only if they match an existing TicketTypeMapping for this event (or if an $existing mapping is provided and matches)
                if ($type->used) {
                    // allow if there is an existing ticket type mapping that matches this provider and external id
                    $existingTypeMapping = TicketTypeMapping::where('ticket_provider_id', $provider->id)
                        ->where('external_id', $id)
                        ->first();
                    if (! $existingTypeMapping) {
                        // If an $existing EventMapping was provided, check if it references the same provider and type (covering edit scenarios)
                        if (! $existing || $existing->ticket_provider_id !== $provider->id || (string)$existing->external_id !== (string)$providerEvent->external_id) {
                            continue;
                        }
                    }
                }
                // ensure not already mapped unless the mapping is for this event's ticket types (allow editing)
                $existsQuery = TicketTypeMapping::where('ticket_provider_id', $provider->id)
                    ->where('external_id', $id);
                $exists = $existsQuery->exists();
                if ($exists) {
                    // if there is an existing mapping but it points to a ticket type on this same event, allow it
                    $existingMapping = TicketTypeMapping::where('ticket_provider_id', $provider->id)
                        ->where('external_id', $id)
                        ->first();
                    if (! $existingMapping || $existingMapping->ticket_type->event_id !== $this->id) {
                        continue;
                    }
                }
                $results[] = (object)[
                    'provider' => $provider,
                    'types' => [$type]
                ];
            }
        }
        return $results;
    }
}
    */

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
