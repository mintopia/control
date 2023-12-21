<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperEvent
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

    public function getAvailableEventMappings(?EventMapping $existing = null): array
    {
        $allProviders = TicketProvider::all();
        $result = [];
        foreach ($allProviders as $provider) {
            $events = array_filter($provider->getEvents(), function ($event) use ($existing, $provider) {
                if (!$event->used) {
                    return true;
                }
                if ($existing && $existing->ticket_provider_id === $provider->id && $existing->external_id === $event->id) {
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
                if ($existing && $existing->ticket_provider_id === $provider->id && $existing->external_id === $type->id) {
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
