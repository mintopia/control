<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Event
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventMapping> $providers
 * @property-read int|null $providers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @method static \Illuminate\Database\Eloquent\Builder|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SeatingPlan> $seatingPlans
 * @property-read int|null $seating_plans_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TicketType> $ticketTypes
 * @property-read int|null $ticket_types_count
 * @property int $seating_locked
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereSeatingLocked($value)
 * @property string|null $boxoffice_url
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereBoxofficeUrl($value)
 * @mixin \Eloquent
 * @mixin IdeHelperEvent
 */
class Event extends Model
{
    use HasFactory;

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
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

    public function fixSeatingPlanOrder(): void
    {
        $plans = $this->seatingPlans()->orderBy('order', 'ASC')->get();
        $order = 1;
        foreach ($plans as $plan) {
            $plan->order = $order;
            if ($plan->isDirty()) {
                $plan->save();
            }
            $order++;
        }
    }
    public function getAvailableTicketMappings(?TicketTypeMapping $existing = null): array
    {
        $allProviders = TicketProvider::all();
        $result = [];
        foreach ($allProviders as $provider) {
            $types = [];
            $allTypes = $provider->getTicketTypes($this);
            foreach ($allTypes as $type) {
                if (!$type->used || ($existing && $existing->ticket_provider_id == $provider->id && $existing->external_id === $type->id)) {
                    $types[] = $type;
                }
            }
            if ($types) {
                $result[] = (object)[
                    'provider' => $provider,
                    'types' => $types,
                ];
            }
        }
        return $result;
    }
}
