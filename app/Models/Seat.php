<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Seat
 *
 * @mixin IdeHelperSeat
 * @property int $id
 * @property int $seating_plan_id
 * @property int|null $seat_group_id
 * @property int|null $ticket_id
 * @property int $x
 * @property int $y
 * @property string $row
 * @property int $number
 * @property string $label
 * @property string|null $description
 * @property string|null $class
 * @property int $disabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SeatGroup|null $group
 * @property-read SeatingPlan $plan
 * @property-read Ticket|null $ticket
 * @method static Builder|Seat newModelQuery()
 * @method static Builder|Seat newQuery()
 * @method static Builder|Seat query()
 * @method static Builder|Seat whereClass($value)
 * @method static Builder|Seat whereCreatedAt($value)
 * @method static Builder|Seat whereDescription($value)
 * @method static Builder|Seat whereDisabled($value)
 * @method static Builder|Seat whereId($value)
 * @method static Builder|Seat whereLabel($value)
 * @method static Builder|Seat whereNumber($value)
 * @method static Builder|Seat whereRow($value)
 * @method static Builder|Seat whereSeatGroupId($value)
 * @method static Builder|Seat whereSeatingPlanId($value)
 * @method static Builder|Seat whereTicketId($value)
 * @method static Builder|Seat whereUpdatedAt($value)
 * @method static Builder|Seat whereX($value)
 * @method static Builder|Seat whereY($value)
 * @mixin Eloquent
 */
class Seat extends Model
{
    use HasFactory;
    use ToString;

    protected $fillable = [
        'row',
        'seat_group_id',
        'seating_plan_id',
        'ticket_id',
        'x',
        'y',
        'number',
        'label',
        'description',
        'class',
        'disabled'
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SeatingPlan::class, 'seating_plan_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SeatGroup::class, 'seat_group_id');
    }

    public function canPick(?User $user = null): bool
    {
        if ($this->disabled) {
            return false;
        }
        if ($this->plan->event->seating_locked) {
            return false;
        }
        if ($user === null) {
            return true;
        }
        $tickets = $user->getPickableTickets($this->plan->event);
        if ($tickets->count() === 0) {
            return false;
        }
        if ($this->group) {
            if (!$user->allowedSeatGroup($this->group)) {
                return false;
            }
        }
        if ($this->ticket) {
            foreach ($tickets as $ticket) {
                if ($ticket->id === $this->ticket->id) {
                    return true;
                }
                return false;
            }
        }
        return true;
    }

    protected function toStringName(): string
    {
        return $this->label;
    }
}
