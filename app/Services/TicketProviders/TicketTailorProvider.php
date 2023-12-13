<?php
namespace App\Services\TicketProviders;

use App\Exceptions\TicketProviderWebhookException;
use App\Models\EmailAddress;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketProvider;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketTailorProvider extends AbstractTicketProvider
{
    protected ?Client $client = null;

    public function configMapping(): array
    {
        return [
            'apikey' => (object)[
                'name' => 'API Key',
                'validation' => 'required|string',
            ],
            'webhook_secret' => (object)[
                'name' => 'Webhook Signing Secret',
                'validation' => 'sometimes|string|nullable',
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
        // No secret, must be valid
        if (!$this->webhookSecret) {
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
        $hash = hash_hmac('sha256', $timestamp . $body, $this->webhookSecret);
        if (!hash_equals($signature, $hash)) {
            throw new TicketProviderWebhookException('Hash does not match signature');
        }
        return true;
    }

    protected function getClient(): Client
    {
        if (!$this->client) {
            $this->client = new Client([
                'base_uri' => config('services.tickettailor.endpoint'),
                'verify' => config('services.tickettailor.verifytls'),
                'auth' => [$this->apikey, '']
            ]);
        }
        return $this->client;
    }
    public function syncTicket(string $id): ?Ticket
    {
        try {
            $response = $this->getClient()->get("/v1/issued_tickets/{$id}");
        } catch (ClientException $exception) {
            if ($exception->getResponse()->getStatusCode() === 404) {
                // Ticket doesn't exist
                $ticket = Ticket::whereTicketProviderId($this->provider->id)->whereExternalId($id)->first();
                if ($ticket) {
                    $ticket->delete();
                }
                return null;
            }
            throw $exception;
        }

        $data = json_decode($response->getBody());
        return $this->processTicket($data);
    }

    protected function processTicket(object $data): ?Ticket
    {
        $ticket = Ticket::whereTicketProviderId($this->provider->id)->whereExternalId($data->id)->first();
        if ($ticket && $data->status === 'voided') {
            Log::info("{$this->provider} {$ticket} Removed. Status is Voided");
            $ticket->delete();
        } elseif (!$ticket) {
            $email = EmailAddress::whereEmail($data->email)->whereNotNull('verified_at')->first();
            if (!$email) {
                Log::info("{$this->provider} {$data->ticket} not added. Unable to find {$data->email}");
                // No email, nothing to do
                return null;
            }
            $event = Event::whereHas('providers', function ($query) use ($data) {
                $query->whereTicketProviderId($this->provider->id)->whereExternalId($data->event_id);
            })->first();
            if (!$event) {
                Log::info("{$this->provider} {$data->ticket} not added. Unable to find event {$data->event_id}");
            }
            $ticket = $this->makeTicket($email->user, $data);
            if (!$ticket) {
                Log::info("{$this->provider} {$ticket} has been added for {$email}");
            }
        }
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
        do {
            $response = $this->getClient()->get('/v1/issued_tickets', [
                'query' => $query,
            ]);
            $data = json_decode($response->getBody());
            foreach ($data->data as $ticket) {
                $query['starting_after'] = $ticket->id;
                if ($ticket->status === 'valid') {
                    $valid[] = $ticket->id;
                    $ticketData[$ticket->id] = $ticket;
                } elseif ($ticket->status === 'voided') {
                    $voided[] = $ticket->id;
                }
            }
        } while ($data->links->next);

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

    protected function makeTicket(User $user, object $data): ?Ticket
    {
        $event = $this->getEvent($data->event_id);
        if (!$event) {
            Log::info("{$this->provider} {$data->ticket} not added. Unable to find event {$data->event_id}");
            return null;
        }
        $type = $this->getType($data->ticket_type_id);
        if (!$type) {
            Log::info("{$this->provider} {$data->ticket} not added. Unable to find ticket type {$data->ticket_type_id}");
            return null;
        }
        $ticket = new Ticket;
        $ticket->provider()->associate($this->provider);
        $ticket->user()->associate($user);
        $ticket->event()->associate($event);
        $ticket->type()->associate($type);
        $ticket->external_id = $data->id;
        $ticket->name = $data->description;
        $ticket->reference = $data->barcode;
        $ticket->qrcode = $this->getQrCode($data);
        $ticket->save();
        return $ticket;
    }

    public function getEvents(): array
    {
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
        return $events;
    }

    public function getTicketTypes(string $eventExternalId): array
    {
        $types = [];
        $response = $this->getClient()->get("/v1/events/{$eventExternalId}");
        $data = json_decode($response->getBody());
        foreach ($data->ticket_types as $type) {
            $types[$type->id] = $type->name;
        }
        return $types;
    }

    protected function getType(string $externalId): ?TicketType
    {
        return TicketType::whereHas('providers', function($query) use ($externalId) {
            $query->whereTicketProviderId($this->provider->id)->whereExternalId($externalId);
        })->first();
    }

    protected function getEvent(string $externalId): ?Event
    {
        return Event::whereHas('providers', function ($query) use ($externalId) {
            $query->whereTicketProviderId($this->provider->id)->whereExternalId($externalId);
        })->first();
    }

    protected function getQrCode(object $data): string
    {
        return str_replace(['/st/', '.jpg'], ['/qr/', '.png'], $data->barcode_url);
    }
}
