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
 * App\Models\Ticket
 *
 * @property int $id
 * @property int $ticket_provider_id
 * @property int $user_id
 * @property string $external_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket query()
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereTicketProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereUserId($value)
 * @property-read \App\Models\TicketProvider|null $provider
 * @property-read \App\Models\User $user
 * @property int $event_id
 * @property string $name
 * @property-read \App\Models\Event $event
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereName($value)
 * @property int $ticket_type_id
 * @property string $reference
 * @property string $qrcode
 * @property-read \App\Models\TicketType $type
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereQrcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereTicketTypeId($value)
 * @property string|null $transfer_code
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereTransferCode($value)
 * @property int|null $seat_id
 * @property-read \App\Models\Seat|null $seat
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereSeatId($value)
 * @mixin \Eloquent
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
}
