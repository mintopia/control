<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\EventMapping
 *
 * @mixin IdeHelperEventMapping
 * @property int $id
 * @property int $event_id
 * @property int $ticket_provider_id
 * @property string $external_id
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \App\Models\TicketProvider $provider
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping query()
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereTicketProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventMapping whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EventMapping extends Model
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
