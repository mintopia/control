<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\SeatingPlan
 *
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string $code
 * @property int $order
 * @property string|null $image_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Seat> $seats
 * @property-read int|null $seats_count
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SeatingPlan extends Model
{
    use HasFactory, ToString;

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    protected function toStringName(): string
    {
        return $this->code;
    }
}
