<?php

namespace App\Models;

use App\Jobs\UpdateSeatingPlanJob;
use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
 * @property int $revision
 * @method static \Illuminate\Database\Eloquent\Builder|SeatingPlan whereRevision($value)
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

    public function updateRevision(): void
    {
        $this->revision++;
        $this->save();
    }

    public function queueUpdate(): void
    {
        UpdateSeatingPlanJob::dispatch($this, $this->revision);
        Log::debug("{$this} updating to revision {$this->revision}");
    }

    public function getData(): Collection
    {
        $key = "seatingplans:{$this->id}:{$this->revision}";
        if (Cache::has($key)) {
            Log::debug("{$this} fetched from cache");
            return Cache::get($key);
        }

        $seats = $this->seats()
            ->with(['ticket', 'ticket.user', 'plan'])
            ->orderBy('row', 'ASC')
            ->orderBy('number', 'ASC')
            ->get();

        $data = new Collection();
        foreach ($seats as $seat) {
            $seatData = (object)[
                'id' => $seat->id,
                'x' => $seat->x,
                'y' => $seat->y,
                'class' => $seat->class,
                'label' => $seat->label,
                'row' => $seat->row,
                'number' => $seat->number,
                'disabled' => $seat->disabled,
                'description' => $seat->description,
                'nickname' => $seat->ticket->user->nickname ?? null,
                'ticket' => $seat->ticket->type->name ?? null,
                'canPick' => $seat->canPick(),
            ];
            $data->push($seatData);
        }

        Cache::put($key, $data);
        Log::debug("{$this} saving revision {$this->revision}");
        return $data;
    }
}
