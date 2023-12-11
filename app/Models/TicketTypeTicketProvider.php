<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\TicketTypeTicketProvider
 *
 * @property int $id
 * @property int $ticket_type_id
 * @property int $ticket_provider_id
 * @property string $external_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TicketProvider $provider
 * @property-read \App\Models\TicketType $type
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeTicketProvider newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeTicketProvider newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeTicketProvider query()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeTicketProvider whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeTicketProvider whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeTicketProvider whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeTicketProvider whereTicketProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeTicketProvider whereTicketTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeTicketProvider whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TicketTypeTicketProvider extends Model
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
