<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\EventTicketProvider
 *
 * @property int $id
 * @property int $event_id
 * @property int $ticket_provider_id
 * @property string $external_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \App\Models\TicketProvider $provider
 * @method static \Illuminate\Database\Eloquent\Builder|EventTicketProvider newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EventTicketProvider newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EventTicketProvider query()
 * @method static \Illuminate\Database\Eloquent\Builder|EventTicketProvider whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventTicketProvider whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventTicketProvider whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventTicketProvider whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventTicketProvider whereTicketProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventTicketProvider whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EventTicketProvider extends Model
{
    use HasFactory, ToString;

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TicketProvider::class, 'ticket_provider_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

}
