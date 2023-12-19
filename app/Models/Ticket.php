<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use function App\makeCode;

/**
 * @mixin IdeHelperTicket
 */
class Ticket extends Model
{
    use HasFactory, ToString;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TicketProvider::class, 'ticket_provider_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function seat(): HasOne
    {
        return $this->hasOne(Seat::class);
    }

    public function generateTransferCode(): void
    {
        $codes = [];
        for ($i = 0; $i < 4; $i++) {
            $codes[] = makeCode(4);
        }
        $this->transfer_code = implode('-', $codes);
        $this->save();
    }

    public function canTransfer(): bool
    {
        if ($this->event->ends_at < Carbon::now()) {
            return false;
        }
        // TODO: Check Ticket Type
        return true;
    }

    public function canPickSeat(): bool
    {
        if (!$this->type->has_seat) {
            return false;
        }
        if ($this->event->ends_at < Carbon::now()) {
            return false;
        }
        if ($this->event->seating_locked) {
            return false;
        }
        return true;
    }

    public function canBeManagedBy(User $user): bool
    {
        if ($user->id === $this->user_id) {
            return true;
        }

        $thisTicketClans = $this->user->clanMemberships()->pluck('clan_id');
        $userClans = $user->clanMemberships()->whereHas('role', function($query) {
            $query->whereIn('code', ['leader', 'seatmanager']);
        })->pluck('clan_id');
        $common = $thisTicketClans->intersect($userClans);
        return $common->count() > 0;
    }
}
