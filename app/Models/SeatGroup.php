<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\SeatGroup
 *
 * @property int $id
 * @property string $name
 * @property int $event_id
 * @property string|null $class
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SeatGroupAssignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read \App\Models\Event $event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Seat> $seats
 * @property-read int|null $seats_count
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroup whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroup whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroup whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroup whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SeatGroup extends Model
{
    use HasFactory, ToString;

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SeatGroupAssignment::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }
}
