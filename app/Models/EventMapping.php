<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperEventMapping
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
