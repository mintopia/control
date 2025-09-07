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
 * App\Models\EventMapping
 *
 * @mixin IdeHelperEventMapping
 * @property int $id
 * @property int $event_id
 * @property int $ticket_provider_id
 * @property string $external_id
 * @property string|null $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Event $event
 * @property-read TicketProvider $provider
 * @method static Builder|EventMapping newModelQuery()
 * @method static Builder|EventMapping newQuery()
 * @method static Builder|EventMapping query()
 * @method static Builder|EventMapping whereCreatedAt($value)
 * @method static Builder|EventMapping whereEventId($value)
 * @method static Builder|EventMapping whereExternalId($value)
 * @method static Builder|EventMapping whereId($value)
 * @method static Builder|EventMapping whereName($value)
 * @method static Builder|EventMapping whereTicketProviderId($value)
 * @method static Builder|EventMapping whereUpdatedAt($value)
 * @mixin Eloquent
 */
class EventMapping extends Model
{
    use HasFactory;
    use ToString;

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TicketProvider::class, 'ticket_provider_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
