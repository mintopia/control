<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperTicketType
 */
class TicketType extends Model
{
    use HasFactory, ToString;

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(TicketTypeMapping::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
