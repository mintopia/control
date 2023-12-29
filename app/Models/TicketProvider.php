<?php

namespace App\Models;

use App\Models\Traits\ToString;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

/**
 * @mixin IdeHelperTicketProvider
 */
class TicketProvider extends Model
{
    use HasFactory, ToString;

    protected $hidden = [
        'apikey',
        'apisecret',
        'webhook_secret',
    ];

    protected $casts = [
        'apikey' => 'encrypted',
        'apisecret' => 'encrypted',
        'webhook_secret' => 'encrypted',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function getProvider(?string $redirectUrl = null): TicketProviderContract
    {
        return new $this->provider_class($this);
    }

    public function syncTickets(EmailAddress $email): void
    {
        $this->getProvider()->syncTickets($email);
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
