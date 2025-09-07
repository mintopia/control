<?php

namespace Tests\Unit\app\Services\TicketProviders\HelperClasses;

use App\Models\Event;
use App\Models\EventMapping;
use App\Models\Ticket;
use App\Models\TicketProvider;
use App\Models\TicketType;
use App\Models\TicketTypeMapping;
use App\Models\User;
use App\Services\TicketProviders\WooCommerceProvider;
use Closure;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

/**
 * Lightweight test helper that exposes protected WooCommerceProvider methods as public
 * so unit tests can call them directly without mocking protected methods.
 */
class DummyWooCommerceProvider extends WooCommerceProvider
{
    /**
     * Constructor must match App\Services\Contracts\TicketProviderContract::__construct
     *
     * @param ?TicketProvider $provider
     */
    public function __construct(?TicketProvider $provider = null)
    {
        $this->provider = $provider;
        parent::__construct($provider);
    }

    public function getTicketsPublic(?string $address = null): array
    {
        // avoid real HTTP calls in unit tests
        return [(object)['id' => 'w1', 'status' => 'valid', 'email' => $address ?? 'a@b.test', 'event_id' => 'evt-1', 'ticket_type_id' => 'type-1', 'barcode' => 'b1', 'description' => 'WC ticket']];
    }

    public function processTicketsPublic(array $ticketData, string $address, ?User $user = null): void
    {
        // ensure fixtures exist for supplied ticket data
        foreach ($ticketData as $d) {
            $this->ensureEventAndTypeExist($d);
        }
        $this->processTickets($ticketData, $address, $user);
    }

    public function makeTicketPublic(?User $user, object $data): ?Ticket
    {
        $this->ensureEventAndTypeExist($data);
        if (!isset($data->reference)) {
            $data->reference = $data->id ?? 'ref';
        }
        // ensure order/item shape expected by makeTicket
        if (!isset($data->order)) {
            $data->order = (object)['billing' => (object)['email' => $data->email ?? 'a@b.test'], 'id' => explode('-', $data->id)[0] ?? 1, 'status' => 'completed'];
        }
        if (!isset($data->item)) {
            $data->item = (object)['id' => explode('-', $data->id)[1] ?? 10, 'name' => $data->description ?? 'Test ticket'];
        }
        return $this->makeTicket($user, $data);
    }

    public function processTicketPublic(object $parsed): ?Ticket
    {
        // parsed is expected to contain order/item keys in WooCommerce provider
        // create expected shape if missing and then create the ticket via makeTicket
        if (!isset($parsed->order)) {
            $parsed->order = (object)['billing' => (object)['email' => $parsed->email ?? 'a@b.test'], 'id' => explode('-', $parsed->id)[0] ?? '1', 'status' => 'completed'];
        }
        if (!isset($parsed->item)) {
            $parsed->item = (object)['id' => explode('-', $parsed->id)[1] ?? '10', 'name' => $parsed->description ?? 'Item'];
        }
        $this->ensureEventAndTypeExist($parsed);
        return $this->makeTicket(null, $parsed);
    }

    public function parseOrderPublic(object $order): array
    {
        return $this->parseOrder($order);
    }

    public function verifyWebhookPublic(Request $request): bool
    {
        return $this->verifyWebhook($request);
    }

    public function getQrCodePublic(object $data): string
    {
        return $this->getQrCode($data);
    }

    public function getClientPublic(): Client
    {
        return $this->getClient();
    }

    public function getTypePublic(string $externalId)
    {
        $type = $this->getType($externalId);
        if ($type) {
            return $type;
        }
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $tm = new TicketTypeMapping();
        $tm->provider()->associate($this->provider);
        $tm->type()->associate($type);
        $tm->external_id = $externalId;
        $tm->save();
        return $type;
    }

    public function getEventsPublic(): array
    {
        // avoid HTTP calls
        return ['evt1' => 'Event 1'];
    }

    public function getTicketTypesPublic(string $eventExternalId): array
    {
        // avoid HTTP calls
        return ['type1' => 'General Admission'];
    }

    protected function ensureEventAndTypeExist(object $data): void
    {
        $id = (string)($data->event_id ?? '');
        if ($id === '' || (strpos($id, 'evt') === false && strpos($id, 'EVT') === false && !is_numeric($id))) {
            return;
        }
        $event = Event::whereHas('mappings', function ($q) use ($data) {
            $q->whereTicketProviderId($this->provider->id)->whereExternalId($data->event_id);
        })->first();
        if (!$event) {
            $event = Event::factory()->create();
            $em = new EventMapping();
            $em->provider()->associate($this->provider);
            $em->event()->associate($event);
            $em->external_id = $data->event_id;
            $em->save();
        }
        $type = TicketType::whereHas('mappings', function ($q) use ($data) {
            $q->whereTicketProviderId($this->provider->id)->whereExternalId($data->ticket_type_id);
        })->first();
        if (!$type) {
            $type = TicketType::factory()->for($event)->create();
            $tm = new TicketTypeMapping();
            $tm->provider()->associate($this->provider);
            $tm->type()->associate($type);
            $tm->external_id = $data->ticket_type_id;
            $tm->save();
        }
    }

    // --- Test override hooks ---

    /** @var bool|null If set, used as the return for verifyWebhook */
    public ?bool $forceVerify = null;

    /** @var array|null If set, used as the return value for parseOrder */
    public ?array $parseOverride = null;

    /** @var bool Flag set when processTickets is invoked */
    public bool $processCalled = false;

    /** @var Closure|null Optional override for processTickets behaviour */
    public $processOverride = null;

    protected function verifyWebhook(Request $request): bool
    {
        if ($this->forceVerify !== null) {
            return $this->forceVerify;
        }
        return parent::verifyWebhook($request);
    }

    protected function parseOrder(object $order): array
    {
        if ($this->parseOverride !== null) {
            return $this->parseOverride;
        }
        return parent::parseOrder($order);
    }

    protected function processTickets(array $ticketData, string $address, ?User $user = null): void
    {
        $this->processCalled = true;
        if ($this->processOverride instanceof Closure) {
            ($this->processOverride)($ticketData, $address, $user);
            return;
        }
        // avoid calling parent by default to keep tests lightweight
    }
}
