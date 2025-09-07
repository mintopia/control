<?php

namespace App\Models;

use App\Models\Traits\ToString;
use App\Services\Contracts\TicketProviderContract;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * App\Models\TicketProvider
 *
 * @mixin IdeHelperTicketProvider
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $provider_class
 * @property int $enabled
 * @property string $cache_prefix
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, EventMapping> $events
 * @property-read int|null $events_count
 * @property-read Collection<int, ProviderSetting> $settings
 * @property-read int|null $settings_count
 * @property-read Collection<int, Ticket> $tickets
 * @property-read int|null $tickets_count
 * @property-read Collection<int, TicketTypeMapping> $types
 * @property-read int|null $types_count
 * @method static Builder|TicketProvider newModelQuery()
 * @method static Builder|TicketProvider newQuery()
 * @method static Builder|TicketProvider query()
 * @method static Builder|TicketProvider whereCachePrefix($value)
 * @method static Builder|TicketProvider whereCode($value)
 * @method static Builder|TicketProvider whereCreatedAt($value)
 * @method static Builder|TicketProvider whereEnabled($value)
 * @method static Builder|TicketProvider whereId($value)
 * @method static Builder|TicketProvider whereName($value)
 * @method static Builder|TicketProvider whereProviderClass($value)
 * @method static Builder|TicketProvider whereUpdatedAt($value)
 * @mixin Eloquent
 */
class TicketProvider extends Model
{
    use HasFactory;
    use ToString;

    protected $fillable = [
        'name',
        'code',
        'provider_class',
        'enabled',
        'cache_prefix',
    ];

    protected array $_settings = [];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function syncTickets(string|EmailAddress $email): void
    {
        $this->getProvider()->syncTickets($email);
    }

    public function getProvider(): TicketProviderContract
    {
        return app()->make($this->provider_class, ['provider' => $this]);
    }

    public function processWebhook(Request $request): bool
    {
        return $this->getProvider()->processWebhook($request);
    }

    public function getEvents(): array
    {
        if (!$this->enabled) {
            return [];
        }

        $events = $this->getProvider()->getEvents();
        $data = [];

        $existing = $this->events;

        foreach ($events as $id => $name) {
            $data[] = (object)[
                'id' => $id,
                'name' => $name,
                'used' => $existing->where('external_id', $id)->count() > 0,
                'used_by' => $existing->where('external_id', $id),
            ];
        }

        return $data;
    }

    public function getTicketTypes(Event $event): array
    {
        if (!$this->enabled) {
            return [];
        }

        $providerEvents = $this->events()->whereEventId($event->id)->get();

        $data = [];
        foreach ($providerEvents as $providerEvent) {
            $types = $this->getProvider()->getTicketTypes($providerEvent->external_id);
            if (!$types) {
                return [];
            }

            $ids = array_keys($types);
            $existing = $this->types()
                ->whereIn('external_id', $ids)
                ->whereHas('type', function ($query) use ($event) {
                    $query->where('event_id', $event->id);
                })->get();

            foreach ($types as $id => $name) {
                $data[] = (object)[
                    'id' => $id,
                    'name' => $name,
                    'used' => $existing->where('external_id', $id)->count() > 0,
                    'used_by' => $existing->where('external_id', $id),
                ];
            }
        }
        return $data;
    }

    public function events(): HasMany
    {
        return $this->hasMany(EventMapping::class);
    }

    public function types(): HasMany
    {
        return $this->hasMany(TicketTypeMapping::class);
    }

    public function getSetting(string $code): mixed
    {
        if (isset($this->_settings[$code])) {
            return $this->_settings[$code];
        }
        $setting = $this->settings()->whereCode($code)->first();
        if (!$setting) {
            $this->_settings[$code] = null;
            return null;
        }
        $this->_settings[$code] = $setting->value;
        return $setting->value;
    }

    public function settings(): MorphMany
    {
        return $this->morphMany(ProviderSetting::class, 'provider');
    }

    public function clearCache(): void
    {
        $this->cache_prefix = time();
        $this->save();
    }

    public function configMapping(): array
    {
        return $this->getProvider()->configMapping();
    }

    protected function toStringName(): string
    {
        return $this->code;
    }
}
