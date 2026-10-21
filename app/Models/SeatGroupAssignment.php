<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\SeatGroupAssignment
 *
 * @property int $id
 * @property int $seat_group_id
 * @property string $assignment_type
 * @property int $assignment_type_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SeatGroup|null $group
 * @method static Builder|SeatGroupAssignment newModelQuery()
 * @method static Builder|SeatGroupAssignment newQuery()
 * @method static Builder|SeatGroupAssignment query()
 * @method static Builder|SeatGroupAssignment whereAssignmentType($value)
 * @method static Builder|SeatGroupAssignment whereAssignmentTypeId($value)
 * @method static Builder|SeatGroupAssignment whereCreatedAt($value)
 * @method static Builder|SeatGroupAssignment whereId($value)
 * @method static Builder|SeatGroupAssignment whereSeatGroupId($value)
 * @method static Builder|SeatGroupAssignment whereUpdatedAt($value)
 * @mixin Eloquent
 */
class SeatGroupAssignment extends Model
{
    use HasFactory;

    public function group(): BelongsTo
    {
        return $this->belongsTo(SeatGroup::class, 'seat_group_id');
    }
}
