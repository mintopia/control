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
 * App\Models\TicketTypeMapping
 *
 * @mixin IdeHelperTicketTypeMapping
 * @property int $id
 * @property int $ticket_type_id
 * @property int $ticket_provider_id
 * @property string $external_id
 * @property string|null $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TicketProvider $provider
 * @property-read TicketType $type
 * @method static Builder|TicketTypeMapping newModelQuery()
 * @method static Builder|TicketTypeMapping newQuery()
 * @method static Builder|TicketTypeMapping query()
 * @method static Builder|TicketTypeMapping whereCreatedAt($value)
 * @method static Builder|TicketTypeMapping whereExternalId($value)
 * @method static Builder|TicketTypeMapping whereId($value)
 * @method static Builder|TicketTypeMapping whereName($value)
 * @method static Builder|TicketTypeMapping whereTicketProviderId($value)
 * @method static Builder|TicketTypeMapping whereTicketTypeId($value)
 * @method static Builder|TicketTypeMapping whereUpdatedAt($value)
 * @mixin Eloquent
 */
class TicketTypeMapping extends Model
{
    use HasFactory;
    use ToString;

    protected $fillable = [
        'ticket_type_id',
        'ticket_provider_id',
        'external_id',
        'name',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TicketProvider::class, 'ticket_provider_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id');
    }
}
