<?php

namespace App\Models;

use App\Models\Helpers\TicketImport;
use App\Models\Traits\ToString;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

use function App\makeCode;

/**
 * App\Models\Ticket
 *
 * @mixin IdeHelperTicket
 * @property int $id
 * @property int $ticket_provider_id
 * @property int|null $user_id
 * @property int $event_id
 * @property int $ticket_type_id
 * @property string $external_id
 * @property string|null $original_email
 * @property string $name
 * @property string $reference
 * @property string|null $qrcode
 * @property string|null $transfer_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \App\Models\TicketProvider $provider
 * @property-read \App\Models\Seat|null $seat
 * @property-read \App\Models\TicketType $type
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket query()
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereOriginalEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereQrcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereTicketProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereTicketTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereTransferCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ticket whereUserId($value)
 * @mixin \Eloquent
 */
class Ticket extends Model
{
    use HasFactory;
    use ToString;

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
        $userClans = $user->clanMemberships()->whereHas('role', function ($query) {
            $query->whereIn('code', ['leader', 'seatmanager']);
        })->pluck('clan_id');
        $common = $thisTicketClans->intersect($userClans);
        return $common->count() > 0;
    }

    public static function import(string $csv): array
    {
        $csv = explode("\n", trim($csv));
        $rows = array_map(function ($row) {
            return str_getcsv(trim($row));
        }, $csv);
        if (!is_numeric($rows[0][0])) {
            // Header row, throw it away
            unset($rows[0]);
        }
        $imports = [];
        foreach ($rows as $row) {
            $type = TicketType::whereId($row[0])->with(['event'])->first();
            if (!$type) {
                continue;
            }
            $user = User::whereId($row[1])->first();
            if (!$user) {
                continue;
            }
            $seat = null;
            if ($row[2] && $type->has_seat) {
                $seat = Seat::whereHas('plan', function ($query) use ($type) {
                    $query->where('event_id', $type->event_id);
                })->whereLabel($row[2])->first();
            }
            $imports[] = new TicketImport($user, $type->event, $type, $seat);
        }
        return $imports;
    }

    public static function createFromImport(TicketImport $import): Ticket
    {
        $provider = TicketProvider::whereCode('internal')->first();

        $ticket = new Ticket();
        $ticket->event()->associate($import->event);
        $ticket->provider()->associate($provider);
        $ticket->user()->associate($import->user);
        $ticket->type()->associate($import->type);

        $ticket->external_id = makeCode(8);
        $ticket->name = $import->type->name;
        $ticket->qrcode = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . $ticket->external_id;
        $ticket->reference = $ticket->external_id;

        DB::transaction(function ($query) use ($import, $ticket) {
            $ticket->save();
            if ($import->seat) {
                $import->seat->ticket()->associate($ticket);
                $import->seat->save();
            }
        });
        return $ticket;
    }
}
