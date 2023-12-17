<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\TicketTypeMapping
 *
 * @property int $id
 * @property int $ticket_type_id
 * @property int $ticket_provider_id
 * @property string $external_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TicketProvider $provider
 * @property-read \App\Models\TicketType $type
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping query()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping whereTicketProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping whereTicketTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeMapping whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperTicketTypeMapping
 */
class TicketTypeMapping extends Model
{
    use HasFactory, ToString;

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TicketProvider::class, 'ticket_provider_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id');
    }
}
