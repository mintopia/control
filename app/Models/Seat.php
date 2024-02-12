<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSeat
 */
class Seat extends Model
{
    use HasFactory, ToString;

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SeatingPlan::class, 'seating_plan_id');
    }

    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clan::class);
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
        if (!$tickets) {
            return false;
        }
        if($this->clan != null && !$this->clan->isMember($user)){
            return false;
        }
        if ($this->ticket) {
            foreach ($tickets as $ticket) {
                if ($ticket->id === $this->ticket->id) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    protected function toStringName(): string
    {
        return $this->label;
    }
}
