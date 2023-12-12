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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventTicketProvider> $providers
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
 */
class Event extends Model
{
    use HasFactory;

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function providers(): HasMany
    {
        return $this->hasMany(EventTicketProvider::class);
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
}
