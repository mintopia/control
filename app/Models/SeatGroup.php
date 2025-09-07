<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\SeatGroup
 *
 * @property int $id
 * @property string $name
 * @property int $event_id
 * @property string|null $class
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, SeatGroupAssignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read Event $event
 * @property-read Collection<int, Seat> $seats
 * @property-read int|null $seats_count
 * @method static Builder|SeatGroup newModelQuery()
 * @method static Builder|SeatGroup newQuery()
 * @method static Builder|SeatGroup query()
 * @method static Builder|SeatGroup whereClass($value)
 * @method static Builder|SeatGroup whereCreatedAt($value)
 * @method static Builder|SeatGroup whereEventId($value)
 * @method static Builder|SeatGroup whereId($value)
 * @method static Builder|SeatGroup whereName($value)
 * @method static Builder|SeatGroup whereUpdatedAt($value)
 * @mixin Eloquent
 */
class SeatGroup extends Model
{
    use HasFactory;
    use ToString;

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
