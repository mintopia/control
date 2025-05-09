<?php

namespace App\Models;

use App\Jobs\UpdateSeatingPlanJob;
use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * App\Models\SeatingPlan
 *
 * @mixin IdeHelperSeatingPlan
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string $code
 * @property int $order
 * @property int $scale
 * @property int $revision
 * @property string|null $image_url
 * @property int|null $image_height
 * @property int|null $image_width
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Seat> $seats
 * @property-read int|null $seats_count
 * @method static Builder|SeatingPlan newModelQuery()
 * @method static Builder|SeatingPlan newQuery()
 * @method static Builder|SeatingPlan ordered(string $direction = 'asc')
 * @method static Builder|SeatingPlan query()
 * @method static Builder|SeatingPlan whereCode($value)
 * @method static Builder|SeatingPlan whereCreatedAt($value)
 * @method static Builder|SeatingPlan whereEventId($value)
 * @method static Builder|SeatingPlan whereId($value)
 * @method static Builder|SeatingPlan whereImageHeight($value)
 * @method static Builder|SeatingPlan whereImageUrl($value)
 * @method static Builder|SeatingPlan whereImageWidth($value)
 * @method static Builder|SeatingPlan whereName($value)
 * @method static Builder|SeatingPlan whereOrder($value)
 * @method static Builder|SeatingPlan whereRevision($value)
 * @method static Builder|SeatingPlan whereScale($value)
 * @method static Builder|SeatingPlan whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SeatingPlan extends Model implements Sortable
{
    use HasFactory;
    use ToString;
    use SortableTrait;

    // Cache the plan for 30 days
    protected const CACHE_TTL = 30 * 86400;

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function buildSortQuery(): Builder
    {
        return static::query()->where('event_id', $this->event_id);
    }

    public function delayedRevisionUpdate(): void
    {
        $this->revision++;
        $this->saveQuietly();
        UpdateSeatingPlanJob::dispatchAfterResponse($this, $this->revision);
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
            ->with(['ticket', 'ticket.user', 'plan', 'ticket.user.clanMemberships.clan', 'group.assignments'])
            ->orderBy('row', 'ASC')
            ->orderBy('number', 'ASC')
            ->get();

        $data = new Collection();
        foreach ($seats as $seat) {
            $clans = [];
            foreach ($seat->ticket->user->clanMemberships ?? [] as $clanMembership) {
                $clans[] = $clanMembership->clan->name;
            }
            $seatData = (object)[
                'id' => $seat->id,
                'x' => $seat->x,
                'y' => $seat->y,
                'class' => $seat->group ? $seat->group->class : $seat->class,
                'label' => $seat->label,
                'row' => $seat->row,
                'number' => $seat->number,
                'disabled' => $seat->disabled,
                'group' => $seat->group ? $seat->group->id : null,
                'description' => $seat->description,
                'nickname' => $seat->ticket->user->nickname ?? null,
                'original_email' => $seat->ticket->original_email ?? null,
                'external_id' => $seat->ticket->external_id ?? null,
                'ticket' => $seat->ticket->type->name ?? null,
                'ticketId' => $seat->ticket->id ?? null,
                'clans' => $clans,
                'canPick' => $seat->canPick(),
            ];
            $data->push($seatData);
        }

        Cache::put($key, $data, self::CACHE_TTL);
        Log::debug("{$this} saving revision {$this->revision}");
        return $data;
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
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

        DB::transaction(function () use ($rows, $wipe) {
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
                $seat->seat_group_id = (int)$row[8] ?? null;
                $seat->disabled = (bool)$row[9];

                $seat->saveQuietly();
            }
        });

        $this->updateRevision();
    }

    public function updateRevision(): void
    {
        $this->revision++;
        $this->save();
    }

    protected function toStringName(): string
    {
        return $this->code;
    }
}
