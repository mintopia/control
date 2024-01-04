<?php

namespace App\Services\TicketProviders;

use App\Models\EmailAddress;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenericTicketProvider extends AbstractTicketProvider
{
    protected const TICKET_TYPES_CACHE_TTL = 86400;
    protected const EVENTS_CACHE_TTL = 86400;

    protected ?Client $client = null;

    protected string $name = 'Generic Provider';
    protected string $code = 'generic';

    public function configMapping(): array
    {
        return [
            'apikey' => (object)[
                'name' => 'API Key',
                'validation' => 'required|string',
            ],
            'endpoint' => (object)[
                'name' => 'Base URL',
                'validation' => 'required|string',
            ]
        ];
    }

    protected function getClient(): Client
    {
        if (!$this->client) {
            $this->client = new Client([
                'base_uri' => $this->provider->endpoint,
                'auth' => [$this->apikey, '']
            ]);
        }
        return $this->client;
    }

    public function processWebhook(Request $request): bool
    {
        $payload = $request->input('payload');
        $this->processTicket((object)$payload);
        return true;
    }

    protected function processTicket(object $data): ?Ticket {
        $ticket = Ticket::whereTicketProviderId($this->provider->id)->whereExternalId($data->id)->first();
        if($ticket && $data->status === 'voided'){
            Log::info("{$this->provider} {$ticket} Removed. Status is Voided");
            $ticket->delete();
        } else if(!$ticket) {
            $email = EmailAddress::whereEmail($data->email)->whereNotNull('verified_at')->first();
            if (!$email) {
                Log::info("{$this->provider} {$data->id} not added. Unable to find {$data->email}");
                // No email, nothing to do
                return null;
            }
            $event = Event::whereHas('mappings', function ($query) use ($data) {
                $query->whereTicketProviderId($this->provider->id)->whereExternalId($data->event_id);
            })->first();
            if (!$event) {
                Log::info("{$this->provider} {$data->id} not added. Unable to find event {$data->event_id}");
                return null;
            }
            $ticket = $this->makeTicket($email->user, $data);
            if ($ticket) {
                Log::info("{$this->provider} {$ticket} has been added for {$email}");
            }
        }
        return $ticket;
    }

    protected function getEvent(string $externalId): ?Event
    {
        return Event::whereHas('mappings', function ($query) use ($externalId) {
            $query->whereTicketProviderId($this->provider->id)->whereExternalId($externalId);
        })->first();
    }

    protected function getType(string $externalId): ?TicketType
    {
        return TicketType::whereHas('mappings', function ($query) use ($externalId) {
            $query->whereTicketProviderId($this->provider->id)->whereExternalId($externalId);
        })->first();
    }

    protected function getQrCode(object $data): string
    {
        return "";
    }

    protected function makeTicket(User $user, object $data): ?Ticket
    {
        $event = $this->getEvent($data->event_id);
        if (!$event) {
            Log::info("{$this->provider} {$data->id} not added. Unable to find event {$data->event_id}");
            return null;
        }
        $type = $this->getType($data->ticket_type_id);
        if (!$type) {
            Log::info("{$this->provider} {$data->id} not added. Unable to find ticket type {$data->ticket_type_id}");
            return null;
        }
        $ticket = new Ticket;
        $ticket->provider()->associate($this->provider);
        $ticket->user()->associate($user);
        $ticket->event()->associate($event);
        $ticket->type()->associate($type);
        $ticket->external_id = $data->id;
        $ticket->name = $data->description;
        $ticket->reference = $data->reference;
        $ticket->qrcode = $this->getQrCode($data);
        $ticket->save();
        return $ticket;
    }

    public function syncTickets(EmailAddress $email): void
    {
        $ticketData = [];
        $valid = [];
        $voided = [];
        $query = [
            'email' => $email->email,
        ];
        $response = $this->getClient()->get("tickets", [
            'query' => $query,
        ]);
        $data = json_decode($response->getBody());
        foreach ($data->tickets as $ticket){
            if ($ticket->status === 'valid') {
                $valid[] = $ticket->id;
                $ticketData[$ticket->id] = $ticket;
            } elseif ($ticket->status === 'voided') {
                $voided[] = $ticket->id;
            }
        }

        // Remove voided - fetch all and delete them in chunks so we trigger delete events
        if ($voided) {
            Ticket::whereTicketProviderId($this->provider->id)->whereIn('id', $voided)->chunk(100, function ($chunk) {
                foreach ($chunk as $ticket) {
                    Log::info("{$this->provider} {$ticket} Removed. Status is Voided");
                    $ticket->delete();
                }
            });
        }

        // Add any missing valid tickets
        $tickets = Ticket::whereTicketProviderId($this->provider->id)->whereIn('external_id', $valid)->get();
        $found = [];
        foreach ($tickets as $ticket) {
            $found[] = $ticket->external_id;
        }
        $missing = array_diff($valid, $found);

        foreach ($missing as $ticketId) {
            $data = $ticketData[$ticketId];
            $ticket = $this->makeTicket($email->user, $data);
            if ($ticket) {
                Log::info("{$this->provider} {$ticket} has been added for {$email}");
            }
        }
    }

    public function getEvents(): array
    {
        $key = "ticketproviders.{$this->provider->id}.{$this->provider->cache_prefix}.events";
        if ($data = Cache::get($key)) {
            Log::info("{$this->provider} Fetching events from Cache");
            return $data;
        }
        Log::info("{$this->provider} Fetching events from API");
        $events = [];
        $response = $this->getClient()->get("events", []);
        $data = json_decode($response->getBody());
        foreach ($data->events as $event) {
            $events[$event->id] = $event->name;
        }
        Cache::put($key, $events, self::EVENTS_CACHE_TTL);
        return $events;
    }

    public function getTicketTypes(string $eventExternalId): array
    {
        $key = "ticketproviders.{$this->provider->id}.{$this->provider->cache_prefix}.events.{$eventExternalId}.tickettypes";
        if ($data = Cache::get($key)) {
            Log::debug("{$this->provider} Fetching ticket types from Cache for {$eventExternalId}");
            return $data;
        }
        Log::info("{$this->provider} Fetching ticket types from API for {$eventExternalId}");

        $types = [];
        $query = [
            'event' => $eventExternalId
        ];
        $response = $this->getClient()->get("tickettypes",
        [
            'query' => $query,
        ]);
        $data = json_decode($response->getBody());
        foreach ($data->ticket_types as $type) {
            $types[$type->id] = $type->name;
        }
        Cache::put($key, $types, self::TICKET_TYPES_CACHE_TTL);
        return $types;
    }
}
