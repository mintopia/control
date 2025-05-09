<?php

namespace App\Services\TicketProviders;

use App\Exceptions\TicketProviderWebhookException;
use App\Models\EmailAddress;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\TicketProviders\Traits\GenericSyncAllTrait;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TicketTailorProvider extends AbstractTicketProvider
{
    use GenericSyncAllTrait;

    protected const TICKET_TYPES_CACHE_TTL = '86400';
    protected const EVENTS_CACHE_TTL = 86400;

    protected ?Client $client = null;

    protected string $name = 'Ticket Tailor';
    protected string $code = 'tickettailor';

    public function configMapping(): array
    {
        return [
            'apikey' => (object)[
                'name' => 'API Key',
                'validation' => 'required|string',
                'encrypted' => true,
            ],
            'webhook_secret' => (object)[
                'name' => 'Webhook Signing Secret',
                'validation' => 'sometimes|string|nullable',
                'encrypted' => true,
            ],
        ];
    }

    public function processWebhook(Request $request): bool
    {
        $this->verifyWebhook($request);
        $payload = $request->input('payload');
        $this->processTicket((object)$payload);
        return true;
    }

    protected function verifyWebhook(Request $request): bool
    {
        $secret = $this->provider->getSetting('webhook_secret');
        if (!$secret) {
            return true;
        }

        $header = $request->header('tickettailor-webhook-signature');
        preg_match('/t=(?<timestamp>\d+),v1=(?<signature>.*)/', $header, $matches);
        if (!isset($matches['timestamp']) || !isset($matches['signature'])) {
            throw new TicketProviderWebhookException('Unable to retrieve signature and timestamp from header');
        }

        $timestamp = (int)$matches['timestamp'];
        $signature = $matches['signature'];

        if ($timestamp < Carbon::now()->subMinutes(5)->timestamp) {
            throw new TicketProviderWebhookException('Webhook message is more than 5 minutes old');
        }

        $body = $request->getContent();
        $hash = hash_hmac('sha256', $timestamp . $body, $secret);
        if (!hash_equals($signature, $hash)) {
            throw new TicketProviderWebhookException('Hash does not match signature');
        }
        return true;
    }

    protected function processTicket(object $data): ?Ticket
    {
        $ticket = Ticket::whereTicketProviderId($this->provider->id)->whereExternalId($data->id)->first();
        if ($ticket && $data->status === 'voided') {
            Log::info("{$this->provider} {$ticket} Removed. Status is Voided");
            $ticket->delete();
        } elseif (!$ticket) {
            $email = EmailAddress::whereEmail($data->email)->whereNotNull('verified_at')->first();
            $event = Event::whereHas('mappings', function ($query) use ($data) {
                $query->whereTicketProviderId($this->provider->id)->whereExternalId($data->event_id);
            })->first();
            if (!$event) {
                Log::info("{$this->provider} {$data->id} not added. Unable to find event {$data->event_id}");
                return null;
            }
            $user = null;
            if ($email) {
                $user = $email->user;
            }
            $ticket = $this->makeTicket($user, $data);
            if ($ticket) {
                Log::info("{$this->provider} {$ticket} has been added for {$data->email}");
            }
        }
        return $ticket;
    }

    protected function makeTicket(?User $user, object $data): ?Ticket
    {
        $event = $this->getEvent($data->event_id);
        if (!$event) {
            Log::debug("{$this->provider} {$data->id} not added. Unable to find event {$data->event_id}");
            return null;
        }
        $type = $this->getType($data->ticket_type_id);
        if (!$type) {
            Log::debug("{$this->provider} {$data->id} not added. Unable to find ticket type {$data->ticket_type_id}");
            return null;
        }
        if (!$user) {
            $email = EmailAddress::whereEmail($data->email)
                ->where('verified_at', '<=', Carbon::now())
                ->with('user')->first();
            if ($email) {
                $user = $email->user;
            }
        }
        $ticket = new Ticket();
        $ticket->provider()->associate($this->provider);
        if ($user) {
            $ticket->user()->associate($user);
        }
        $ticket->event()->associate($event);
        $ticket->type()->associate($type);
        $ticket->external_id = $data->id;
        $ticket->original_email = $data->email;
        $ticket->name = $data->description;
        $ticket->reference = $data->barcode;
        $ticket->qrcode = $this->getQrCode($data);
        $ticket->save();
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
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$data->barcode}";
    }

    protected function getClient(): Client
    {
        if (!$this->client) {
            $this->client = new Client([
                'base_uri' => config('services.tickettailor.endpoint'),
                'verify' => config('services.tickettailor.verifytls'),
                'auth' => [$this->provider->getSetting('apikey'), '']
            ]);
        }
        return $this->client;
    }

    protected function getTickets(?string $address = null): array
    {
        $query = [];
        if ($address) {
            $query['email'] = $address;
        }
        $tickets = [];
        do {
            $response = $this->getClient()->get('/v1/issued_tickets', [
                'query' => $query,
            ]);
            $data = json_decode($response->getBody());
            foreach ($data->data as $ticket) {
                $query['starting_after'] = $ticket->id;
                $tickets[$ticket->id] = $ticket;
            }
        } while ($data->links->next);
        return $tickets;
    }

    public function syncTickets(string|EmailAddress $email): void
    {
        $user = null;
        if ($email instanceof EmailAddress) {
            $address = $email->email;
            $user = $email->user;
        } else {
            $address = $email;
        }

        $valid = [];
        $voided = [];
        $ticketData = [];

        $allTickets = $this->getTickets($address);
        foreach ($allTickets as $ticket) {
            if ($ticket->status === 'valid') {
                $valid[] = $ticket->id;
                $ticketData[$ticket->id] = $ticket;
            } else {
                $voided[] = $ticket->id;
            }
        }

        // Remove voided - fetch all and delete them in chunks so we trigger delete events
        if ($voided) {
            Ticket::whereTicketProviderId($this->provider->id)->whereIn('external_id', $voided)->chunk(100, function ($chunk) {
                foreach ($chunk as $ticket) {
                    Log::info("{$this->provider} {$ticket} Removed. Status is Voided");
                    $ticket->delete();
                }
            });
        }

        // Add any missing valid tickets
        $tickets = Ticket::whereTicketProviderId($this->provider->id)->whereIn('external_id', $valid)->with('user')->get();
        $found = [];
        foreach ($tickets as $ticket) {
            $found[] = $ticket->external_id;
            // Match up with our user if we have one
            if ($user && !$ticket->user) {
                $ticket->user()->associate($user);
                $ticket->save();
                Log::info("{$this->provider} {$ticket} has been allocated to {$user}");
            }
        }
        $missing = array_diff($valid, $found);

        // Add the ticket
        foreach ($missing as $ticketId) {
            $data = $ticketData[$ticketId];
            $ticket = $this->makeTicket($user, $data);
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
        $query = [];
        do {
            $response = $this->getClient()->get('/v1/events', [
                'query' => $query,
            ]);
            $data = json_decode($response->getBody());
            foreach ($data->data as $event) {
                $query['starting_after'] = $event->id;
                $events[$event->id] = $event->name;
            }
        } while ($data->links->next !== null);
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
        $response = $this->getClient()->get("/v1/events/{$eventExternalId}");
        $data = json_decode($response->getBody());
        foreach ($data->ticket_types as $type) {
            $types[$type->id] = $type->name;
        }
        Cache::put($key, $types, self::TICKET_TYPES_CACHE_TTL);
        return $types;
    }
}
