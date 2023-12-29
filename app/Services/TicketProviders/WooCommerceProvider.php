<?php

namespace App\Services\TicketProviders;

use App\Exceptions\TicketProviderWebhookException;
use App\Models\EmailAddress;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class WooCommerceProvider extends AbstractTicketProvider
{
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
            ],
            'apisecret' => (object)[
                'name' => 'Consumer Secret',
                'validation' => 'required|string',
            ],
        ];
    }

    public function processWebhook(Request $request): bool
    {
        return true;
    }

    protected function makeTicket(User $user, object $data): ?Ticket
    {
        $type = $this->getType($data->ticket_type_id);
        if (!$type) {
            Log::info("{$this->provider} {$data->id} not added. Unable to find ticket type {$data->ticket_type_id}");
            return null;
        }
        $ticket = new Ticket;
        $ticket->provider()->associate($this->provider);
        $ticket->user()->associate($user);
        $ticket->type()->associate($type);
        $ticket->event()->associate($type->event);
        $ticket->external_id = $data->id;
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
                'base_uri' => $this->provider->endpoint,
                'verify' => config('services.woocommerce.verifytls', true),
                'auth' => [$this->provider->apikey, $this->provider->apisecret],
            ]);
        }
        return $this->client;
    }

    public function syncTickets(EmailAddress $email): void
    {
        $orders = [];
        $page = 1;
        do {
            $response = $this->getClient()->get('orders', [
                'query' => [
                    'search' => $email->email,
                    'page' => $page,
                ]
            ]);
            $data = json_decode($response->getBody());
            foreach ($data as $ticket) {
                if ($ticket->billing->email == $email->email) {
                    $orders[] = $ticket;
                }
            }
            $page++;
        } while (count($data) > 0);

        // Crack the orders into separate tickets
        $valid = [];
        $voided = [];
        $ticketData = [];
        foreach ($orders as $order) {
            $tickets = [];

            foreach ($order->line_items as $item) {
                for ($i = 1; $i <= $item->quantity; $i++) {
                    $externalId = "{$order->id}-{$item->id}-{$i}";
                    $tickets[] = $externalId;
                    $ticketData[$externalId] = (object)[
                        'id' => $externalId,
                        'ticket_type_id' => $item->product_id,
                        'order' => $order,
                        'item' => $item,
                    ];
                }
            }

            if (in_array($order->status, ['processing', 'completed'])) {
                $valid = array_merge($valid, $tickets);
            } else {
                $voided = array_merge($voided, $tickets);
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
