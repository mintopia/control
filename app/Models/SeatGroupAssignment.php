<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * App\Models\SeatGroupAssignment
 *
 * @property int $id
 * @property int $seat_group_id
 * @property string $assignment_type
 * @property int $assignment_type_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SeatGroup|null $group
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroupAssignment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroupAssignment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroupAssignment query()
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroupAssignment whereAssignmentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroupAssignment whereAssignmentTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroupAssignment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroupAssignment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroupAssignment whereSeatGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SeatGroupAssignment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SeatGroupAssignment extends Model
{
    use HasFactory;

    public function group(): BelongsTo
    {
        return $this->belongsTo(SeatGroup::class, 'seat_group_id');
    }
}
