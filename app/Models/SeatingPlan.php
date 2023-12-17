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
use Illuminate\Support\Facades\DB;
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
 * @mixin IdeHelperSeatingPlan
 */
class SeatingPlan extends Model
{
    use HasFactory, ToString;

    // Cache the plan for 30 days
    protected const CACHE_TTL = 30 * 86400;

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
        if ($data = Cache::get($key)) {
            Log::debug("{$this} fetched from cache");
            return $data;
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
                'ticketId' => $seat->ticket->id ?? null,
                'canPick' => $seat->canPick(),
            ];
            $data->push($seatData);
        }

        Cache::put($key, $data, self::CACHE_TTL);
        Log::debug("{$this} saving revision {$this->revision}");
        return $data;
    }

    public function import(string $csv, bool $wipe = false): void
    {
        $csv = explode("\n", trim($csv));
        $rows = array_map(function ($row) {
            return str_getcsv(trim($row));
        }, $csv);
        if ($rows[0][0] === 'ID') {
            // Header row, throw it away
            unset($rows[0]);
        }

        DB::transaction(function() use ($rows, $wipe) {
            if ($wipe) {
                $this->seats()->delete();
            }

            foreach ($rows as $row) {
                $seat = null;
                if (!$row[5]) {
                    continue;
                }
                if ((int)$row[0] > 0) {
                    $seat = $this->seats->where('id', $row[0])->first();
                }
                if ($seat === null) {
                    $seat = new Seat();
                    $seat->plan()->associate($this);
                }

                $seat->x = (int)$row[1];
                $seat->y = (int)$row[2];
                $seat->row = $row[3];
                $seat->number = (int)$row[4];
                $seat->label = $row[5];
                $seat->description = $row[6] ?? null;
                $seat->class = $row[7] ?? null;
                $seat->disabled = (bool)$row[8];

                $seat->saveQuietly();
            }
        });

        $this->updateRevision();
    }
}
