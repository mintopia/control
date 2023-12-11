<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\Seat
 *
 * @property int $id
 * @property int $seating_plan_id
 * @property int $x
 * @property int $y
 * @property string $row
 * @property int $number
 * @property string $label
 * @property string|null $description
 * @property string|null $class
 * @property int $disabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SeatingPlan $plan
 * @property-read \App\Models\Ticket|null $ticket
 * @method static \Illuminate\Database\Eloquent\Builder|Seat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Seat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Seat query()
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereRow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereSeatingPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereX($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Seat whereY($value)
 * @mixin \Eloquent
 */
class Seat extends Model
{
    use HasFactory, ToString;

    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SeatingPlan::class, 'seating_plan_id');
    }

    protected function toStringName(): string
    {
        return $this->label;
    }
}
