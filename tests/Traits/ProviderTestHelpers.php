<?php

namespace Tests\Traits;

use App\Models\SocialProvider;
use App\Models\TicketProvider;
use App\Models\User;
use App\Services\Contracts\SocialProviderContract;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Http\RedirectResponse;
use ReflectionClass;

trait ProviderTestHelpers
{
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
        return new class ($model) extends \App\Services\TicketProviders\AbstractTicketProvider {
            protected string $name = 'Dummy Provider';
            protected string $code = 'dummy';
            public function __construct($provider = null)
            {
                parent::__construct($provider);
            }
            public function configMapping(): array
            {
                return [
                    'apikey' => [
                        'name' => 'API Key',
                        'validation' => 'required|string',
                        'value' => 'dummy-key',
                    ],
                ];
            }
            public function install(): TicketProvider
            {
                return new TicketProvider(['name' => 'Dummy', 'code' => 'dummy']);
            }
            public function processWebhook(\Illuminate\Http\Request $request): bool
            {
                return true;
            }
            public function syncTickets(string|\App\Models\EmailAddress $email): void
            {
                // dummy
            }
            public function syncAllTickets(?\Illuminate\Console\OutputStyle $output): void
            {
                // dummy
            }
            public function getEvents(): array
            {
                return ['evt1' => 'Event 1', 'evt2' => 'Event 2'];
            }
            public function getTicketTypes(string $eventExternalId): array
            {
                return ['type1' => 'VIP', 'type2' => 'Standard'];
            }
        };
    }

    protected function assertImplementsInterface(object $obj, string $interface)
    {
        $rc = new ReflectionClass($obj);
        \PHPUnit\Framework\Assert::assertTrue($rc->implementsInterface($interface));
    }
}
