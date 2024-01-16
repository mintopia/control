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
use GuzzleHttp\Exception\ClientException;
use Illuminate\Console\OutputStyle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PharIo\Manifest\Email;
use Ramsey\Uuid\Uuid;

class WooCommerceProvider extends AbstractTicketProvider
{
    use GenericSyncAllTrait;

    protected const TICKET_TYPES_CACHE_TTL = '86400';
    protected const EVENTS_CACHE_TTL = 86400;

    protected ?Client $client = null;

    protected string $name = 'Woo Commerce';
    protected string $code = 'woocommerce';

    public function configMapping(): array
    {
        return [
            'endpoint' => (object)[
                'name' => 'API Endpoint',
                'validation' => 'required|string|url:http,https',
            ],
            'apikey' => (object)[
                'name' => 'Consumer Key',
                'validation' => 'required|string',
                'encrypted' => true,
            ],
            'apisecret' => (object)[
                'name' => 'Consumer Secret',
                'validation' => 'required|string',
                'encrypted' => true,
            ],
            'webhook_secret' => (object)[
                'name' => 'Webhook Secret',
                'validation' => 'string',
                'encrypted' => true,
            ],
        ];
    }


    public function processWebhook(Request $request): bool
    {
        $this->verifyWebhook($request);
        $data = json_decode($request->getContent());
        $parsed = $this->parseOrder($data);
        if ($parsed) {
            foreach ($parsed as $ticket) {
                $tickets[$ticket->id] = $ticket;
            }
            $this->processTickets($tickets, $data->billing->email);
        }
        return true;
    }

    protected function verifyWebhook(Request $request): bool
    {
        $secret = $this->provider->getSetting('webhook_secret');
        if (!$secret) {
            throw new TicketProviderWebhookException('No webhook secret is configured');
        }

        $signature = base64_decode($request->header('x-wc-webhook-signature'));
        if (!$signature) {
            throw new TicketProviderWebhookException('Unable to retrieve signature from header');
        }

        $content = (string)$request->getContent();
        $hash = hash_hmac('sha256', $content, $secret, true);

        if (!hash_equals($signature, $hash)) {
            throw new TicketProviderWebhookException('Hash does not match signature');
        }
        return true;
    }

    protected function makeTicket(?User $user, object $data): ?Ticket
    {
        $type = $this->getType($data->ticket_type_id);
        if (!$type) {
            Log::debug("{$this->provider} {$data->id} not added. Unable to find ticket type {$data->ticket_type_id}");
            return null;
        }
        if (!$user) {
            $email = EmailAddress::whereEmail($data->order->billing->email)
                ->where('verified_at', '<=', Carbon::now())
                ->with('user')->first();
            if ($email) {
                $user = $email->user;
            }
        }
        $ticket = new Ticket;
        $ticket->provider()->associate($this->provider);
        if ($user) {
            $ticket->user()->associate($user);
        }
        $ticket->type()->associate($type);
        $ticket->event()->associate($type->event);
        $ticket->external_id = $data->id;
        $ticket->original_email = $data->order->billing->email;
        $ticket->name = $data->item->name;
        $ticket->reference = $data->id;
        $ticket->qrcode = $this->getQrCode($data);
        $ticket->save();
        return $ticket;
    }

    protected function getType(string $externalId): ?TicketType
    {
        return TicketType::whereHas('mappings', function ($query) use ($externalId) {
            $query->whereTicketProviderId($this->provider->id)->whereExternalId($externalId);
        })->first();
    }

    protected function getQrCode(object $data): string
    {
        return "https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl={$data->id}";
    }

    protected function getClient(): Client
    {
        if (!$this->client) {
            $this->client = new Client([
                'base_uri' => $this->provider->getSetting('endpoint'),
                'verify' => config('services.woocommerce.verifytls', true),
                'auth' => [$this->provider->getSetting('apikey'), $this->provider->getSetting('apisecret')],
            ]);
        }
        return $this->client;
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

        $ticketData = $this->getTickets($address);
        $this->processTickets($ticketData, $address, $user);
    }

    protected function processTickets(array $ticketData, string $address, ?User $user = null): void
    {
        $tickets = [];
        $valid = [];
        $voided = [];

        foreach ($ticketData as $ticket) {
            $tickets[] = $ticket->id;
            if (in_array($ticket->order->status, ['processing', 'completed'])) {
                $valid[] = $ticket->id;
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
        $tickets = Ticket::whereTicketProviderId($this->provider->id)->whereIn('external_id', $valid)->get();
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

        foreach ($missing as $ticketId) {
            $data = $ticketData[$ticketId];
            $ticket = $this->makeTicket($user, $data);
            if ($ticket) {
                Log::info("{$this->provider} {$ticket} has been added for {$address}");
            }
        }
    }

    protected function getTickets(?string $address = null): array
    {
        $orders = [];
        $query = [
            'page' => 1,
        ];
        if ($address) {
            $query['search'] = $address;
        }
        do {
            $response = $this->getClient()->get('orders', [
                'query' => $query
            ]);
            $data = json_decode($response->getBody());
            foreach ($data as $order) {
                if (!$address || $order->billing->email == $address) {
                    $orders[] = $order;
                }
            }
            $query['page']++;
        } while (count($data) > 0);

        // Crack the orders into separate tickets
        $tickets = [];
        foreach ($orders as $order) {
            $parsed = $this->parseOrder($order);
            foreach ($parsed as $ticket) {
                $tickets[$ticket->id] = $ticket;
            }
        }
        return $tickets;
    }

    protected function parseOrder(object $order): array
    {
        $tickets = [];
        foreach ($order->line_items as $item) {
            for ($i = 1; $i <= $item->quantity; $i++) {
                $externalId = "{$order->id}-{$item->id}-{$i}";
                $status = 'voided';
                if (in_array($order->status, ['processing', 'completed'])) {
                    $status = 'valid';
                }
                $tickets[$externalId] = (object)[
                    'id' => $externalId,
                    'ticket_type_id' => $item->product_id,
                    'order' => $order,
                    'item' => $item,
                    'status' => $status,
                    'email' => $order->billing->email,
                ];
            }
        }
        return $tickets;
    }

    public function getEvents(): array
    {
        $events = [];
        $id = 1;
        foreach ($this->provider->events as $mapping) {
            $events[$mapping->external_id] = $mapping->event->name;
            $id = max($id, $mapping->external_id + 1);
        }
        $events[$id] = 'New Event';
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
        $page = 1;
        do {
            $response = $this->getClient()->get("products", [
                'query' => [
                    'page' => $page,
                ],
            ]);
            $data = json_decode($response->getBody());
            foreach ($data as $product) {
                $types[$product->id] = $product->name;
            }
            $page++;
        } while (count($data) > 0);
        Cache::put($key, $types, self::TICKET_TYPES_CACHE_TTL);
        return $types;
    }
}
