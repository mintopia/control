<?php

namespace Tests\Traits;

use App\Models\SocialProvider;
use App\Models\TicketProvider;
use App\Models\User;
use App\Services\Contracts\SocialProviderContract;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Http\RedirectResponse;
use ReflectionClass;
use Tests\Traits\ReflectionHelpers;

trait ProviderTestHelpers
{
    use ReflectionHelpers;

    protected function makeSocialProvider(): SocialProviderContract
    {
        return new class implements SocialProviderContract {
            public function __construct(?SocialProvider $provider = null, ?string $redirectUrl = null)
            {
            }
            public function configMapping(): array
            {
                return ['client_id' => ['name' => 'Client ID', 'validation' => 'required|string', 'value' => 'dummy-client-id']];
            }
            public function install(): SocialProvider
            {
                return new SocialProvider(['name' => 'Dummy', 'code' => 'dummy']);
            }
            public function redirect(): RedirectResponse
            {
                return new RedirectResponse('/dummy-redirect');
            }
            public function user(?User $localUser = null)
            {
                return $localUser ?: new User(['name' => 'Dummy User']);
            }
        };
    }

    protected function makeSocialProviderVariant(?\App\Models\SocialProvider $provider = null): \App\Services\SocialProviders\AbstractSocialProvider
    {
        return new class ($provider) extends \App\Services\SocialProviders\AbstractSocialProvider {
            protected string $name = 'Dummy Social';
            protected string $code = 'dummy';
            protected string $socialiteProviderCode = 'dummy';

            public function __construct(?\App\Models\SocialProvider $provider = null, ?string $redirectUrl = null)
            {
                parent::__construct($provider, $redirectUrl);
            }

            protected function updateAccount(\App\Models\LinkedAccount $account, $remoteUser): void
            {
                // intentionally empty for tests
            }
        };
    }

    protected function makeTicketProvider(?TicketProvider $model = null): TicketProviderContract
    {
        return new class ($model) extends \App\Services\TicketProviders\GenericTicketProvider {
            // expose provider model publicly for tests that inspect $provider->provider
            public ?\App\Models\TicketProvider $provider = null;

            protected string $name = 'Dummy Provider';
            protected string $code = 'dummy';

            public function __construct($provider = null)
            {
                parent::__construct($provider);
                $this->provider = $provider;
            }

            public function configMapping(): array
            {
                return [
                    'apikey' => (object)[
                        'name' => 'API Key',
                        'validation' => 'required|string',
                        'value' => 'dummy-key',
                        'encrypted' => true,
                    ],
                    'endpoint' => (object)[
                        'name' => 'Base URL',
                        'validation' => 'required|string',
                    ],
                ];
            }

            public function install(): TicketProvider
            {
                return new TicketProvider(['name' => 'Dummy', 'code' => 'dummy']);
            }
        };
    }

    /**
     * Return a test double for TicketTailorProvider that exposes protected methods as public
     * so tests can call them directly.
     */
    protected function makeTicketTailorProvider(?\App\Models\TicketProvider $provider = null)
    {
        return new class ($provider) extends \App\Services\TicketProviders\TicketTailorProvider {
            public ?\App\Models\TicketProvider $provider = null;

            public function __construct(?\App\Models\TicketProvider $provider = null)
            {
                parent::__construct($provider);
                $this->provider = $provider;
            }

            // Tests should call protected helpers via ReflectionHelpers::callProtected.
            // The helper ensureEventAndTypeExist remains implemented below so protected
            // methods that rely on DB fixtures will work when invoked via callProtected.

            /**
             * Ensure there is an Event and TicketType with mappings for the provider so
             * protected methods that rely on DB lookups succeed during tests.
             */
            protected function ensureEventAndTypeExist(object $data): void
            {
                // Only create fixtures automatically for event IDs that look like real provider ids
                $id = (string)($data->event_id ?? '');
                if ($id === '' || (strpos($id, 'evt') === false && strpos($id, 'EVT') === false && !is_numeric($id))) {
                    // leave alone - tests expecting missing event should get null
                    return;
                }

                // Create or find an Event
                $event = \App\Models\Event::whereHas('mappings', function ($q) use ($data) {
                    $q->whereTicketProviderId($this->provider->id)->whereExternalId($data->event_id);
                })->first();
                if (!$event) {
                    $event = \App\Models\Event::factory()->create();
                    $em = new \App\Models\EventMapping();
                    $em->provider()->associate($this->provider);
                    $em->event()->associate($event);
                    $em->external_id = $data->event_id;
                    $em->save();
                }

                // Create or find TicketType mapping
                $type = \App\Models\TicketType::whereHas('mappings', function ($q) use ($data) {
                    $q->whereTicketProviderId($this->provider->id)->whereExternalId($data->ticket_type_id);
                })->first();
                if (!$type) {
                    $type = \App\Models\TicketType::factory()->for($event)->create();
                    $tm = new \App\Models\TicketTypeMapping();
                    $tm->provider()->associate($this->provider);
                    $tm->type()->associate($type);
                    $tm->external_id = $data->ticket_type_id;
                    $tm->save();
                }
            }

            // Stub network-fetching methods so tests that invoke protected methods
            // via reflection do not perform real HTTP calls.
            public function getEvents(): array
            {
                return [];
            }

            protected function getTickets(?string $address = null): array
            {
                return [];
            }

            public function getTicketTypes(string $eventExternalId): array
            {
                return [];
            }

            protected function getType(string $externalId): ?\App\Models\TicketType
            {
                $type = parent::getType($externalId);
                if ($type) {
                    return $type;
                }
                // Create an Event and TicketType with mapping for the provider
                $event = \App\Models\Event::factory()->create();
                $type = \App\Models\TicketType::factory()->for($event)->create();
                $tm = new \App\Models\TicketTypeMapping();
                $tm->provider()->associate($this->provider);
                $tm->type()->associate($type);
                $tm->external_id = $externalId;
                $tm->save();
                return $type;
            }

            protected function makeTicket(?\App\Models\User $user, object $data): ?\App\Models\Ticket
            {
                $this->ensureEventAndTypeExist($data);
                if (!isset($data->barcode)) {
                    $data->barcode = $data->id ?? ($data->reference ?? null);
                }
                return parent::makeTicket($user, $data);
            }

            protected function processTicket(object $data): ?\App\Models\Ticket
            {
                // Ensure event/type exist so parent::processTicket can find them
                $this->ensureEventAndTypeExist($data);
                return parent::processTicket($data);
            }
        };
    }

    /**
     * Return a test double for WooCommerceProvider similar to DummyWooCommerceProvider helper.
     */
    protected function makeWooCommerceProvider(?\App\Models\TicketProvider $provider = null)
    {
        return new class ($provider) extends \App\Services\TicketProviders\WooCommerceProvider {
            public ?\App\Models\TicketProvider $provider = null;
            public ?bool $forceVerify = null;
            public ?array $parseOverride = null;
            public bool $processCalled = false;
            public $processOverride = null;

            public function __construct(?\App\Models\TicketProvider $provider = null)
            {
                parent::__construct($provider);
                $this->provider = $provider;
            }

            // Tests should use ReflectionHelpers::callProtected to invoke protected
            // provider methods (for example: $this->callProtected($dummy, 'getTickets', [$addr])).

            // Provide protected overrides so tests that relied on public wrapper
            // behaviour still work when invoking protected methods via
            // ReflectionHelpers::callProtected.
            protected function getTickets(?string $address = null): array
            {
                return [(object)['id' => 'w1', 'status' => 'valid', 'email' => $address ?? 'a@b.test', 'event_id' => 'evt-1', 'ticket_type_id' => 'type-1', 'barcode' => 'b1', 'description' => 'WC ticket']];
            }

            protected function processTickets(array $ticketData, string $address, ?\App\Models\User $user = null): void
            {
                foreach ($ticketData as $d) {
                    $this->ensureEventAndTypeExist($d);
                }
                // mark for assertions in tests
                $this->processCalled = true;
            }

            protected function makeTicket(?\App\Models\User $user, object $data): ?\App\Models\Ticket
            {
                $this->ensureEventAndTypeExist($data);
                if (!isset($data->reference)) {
                    $data->reference = $data->id ?? 'ref';
                }
                if (!isset($data->order)) {
                    $data->order = (object)['billing' => (object)['email' => $data->email ?? 'a@b.test'], 'id' => explode('-', $data->id)[0] ?? 1, 'status' => 'completed'];
                }
                if (!isset($data->item)) {
                    $data->item = (object)['id' => explode('-', $data->id)[1] ?? 10, 'name' => $data->description ?? 'Test ticket'];
                }

                // Ensure barcode exists for getQrCode
                if (!isset($data->barcode)) {
                    $data->barcode = $data->id ?? ($data->reference ?? null);
                }
                // Call parent so email lookup and associations run as in production
                return parent::makeTicket($user, $data);
            }

            protected function processTicket(object $parsed): ?\App\Models\Ticket
            {
                if (!isset($parsed->order)) {
                    $parsed->order = (object)['billing' => (object)['email' => $parsed->email ?? 'a@b.test'], 'id' => explode('-', $parsed->id)[0] ?? '1', 'status' => 'completed'];
                }
                if (!isset($parsed->item)) {
                    $parsed->item = (object)['id' => explode('-', $parsed->id)[1] ?? '10', 'name' => $parsed->description ?? 'Item'];
                }
                $this->ensureEventAndTypeExist($parsed);
                return $this->makeTicket(null, $parsed);
            }

            protected function parseOrder(object $order): array
            {
                if ($this->parseOverride !== null) {
                    return $this->parseOverride;
                }
                return parent::parseOrder($order);
            }

            protected function verifyWebhook(\Illuminate\Http\Request $request): bool
            {
                if ($this->forceVerify !== null) {
                    return $this->forceVerify;
                }
                return parent::verifyWebhook($request);
            }

            protected function getType(string $externalId): ?\App\Models\TicketType
            {
                $type = parent::getType($externalId);
                if ($type) {
                    return $type;
                }
                $event = \App\Models\Event::factory()->create();
                $type = \App\Models\TicketType::factory()->for($event)->create();
                $tm = new \App\Models\TicketTypeMapping();
                $tm->provider()->associate($this->provider);
                $tm->type()->associate($type);
                $tm->external_id = $externalId;
                $tm->save();
                return $type;
            }

            public function getTicketTypes(string $eventExternalId): array
            {
                return [];
            }

            // No public wrapper methods here; tests should use callProtected when
            // they need to invoke protected provider methods.

            protected function ensureEventAndTypeExist(object $data): void
            {
                $id = (string)($data->event_id ?? '');
                if ($id === '' || (strpos($id, 'evt') === false && strpos($id, 'EVT') === false && !is_numeric($id))) {
                    return;
                }
                $event = \App\Models\Event::whereHas('mappings', function ($q) use ($data) {
                    $q->whereTicketProviderId($this->provider->id)->whereExternalId($data->event_id);
                })->first();
                if (!$event) {
                    $event = \App\Models\Event::factory()->create();
                    $em = new \App\Models\EventMapping();
                    $em->provider()->associate($this->provider);
                    $em->event()->associate($event);
                    $em->external_id = $data->event_id;
                    $em->save();
                }
                $type = \App\Models\TicketType::whereHas('mappings', function ($q) use ($data) {
                    $q->whereTicketProviderId($this->provider->id)->whereExternalId($data->ticket_type_id);
                })->first();
                if (!$type) {
                    $type = \App\Models\TicketType::factory()->for($event)->create();
                    $tm = new \App\Models\TicketTypeMapping();
                    $tm->provider()->associate($this->provider);
                    $tm->type()->associate($type);
                    $tm->external_id = $data->ticket_type_id;
                    $tm->save();
                }
            }
        };
    }

    // Reflection helpers are provided by the ReflectionHelpers trait.



    /**
     * Return a dummy Discord provider compatible with the controller tests.
     */
    protected function makeDummyDiscordProvider(): \App\Services\Contracts\SocialProviderContract
    {
        return new class implements \App\Services\Contracts\SocialProviderContract {
            public $provider;
            public $redirectUrl;
            public function __construct($provider = null, $redirectUrl = null)
            {
                $this->provider = $provider;
                $this->redirectUrl = $redirectUrl;
            }
            public function configMapping(): array
            {
                return [];
            }
            public function install(): \App\Models\SocialProvider
            {
                return $this->provider;
            }
            public function redirect(): \Illuminate\Http\RedirectResponse
            {
                return redirect()->to('/');
            }
            public function user(?\App\Models\User $localUser = null)
            {
                return null;
            }
            public function addBotToServer()
            {
                return 'added';
            }
            public function bot()
            {
                return (object)['accessTokenResponseBody' => ['guild' => ['name' => 'G1', 'id' => '123']]];
            }
        };
    }

    /**
     * Return a provider that throws from bot() for failure testing.
     */
    protected function makeThrowingDiscordProvider(): \App\Services\Contracts\SocialProviderContract
    {
        return new class implements \App\Services\Contracts\SocialProviderContract {
            public $provider;
            public $redirectUrl;
            public function __construct($provider = null, $redirectUrl = null)
            {
                $this->provider = $provider;
                $this->redirectUrl = $redirectUrl;
            }
            public function configMapping(): array
            {
                return [];
            }
            public function install(): \App\Models\SocialProvider
            {
                return $this->provider;
            }
            public function redirect(): \Illuminate\Http\RedirectResponse
            {
                return redirect()->to('/');
            }
            public function user(?\App\Models\User $localUser = null)
            {
                return null;
            }
            public function bot()
            {
                throw new \Exception('fail');
            }
            public function addBotToServer()
            {
                return 'added';
            }
        };
    }
}
