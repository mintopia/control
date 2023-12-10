<?php
namespace App\Services\TicketProviders;

use App\Models\EmailAddress;
use App\Models\Ticket;
use App\Models\TicketProvider;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Http\Request;

abstract class AbstractTicketProvider implements TicketProviderContract
{
    protected string $name;
    protected string $code;
    protected ?TicketProvider $provider = null;
    protected string $apikey;
    protected string $webhookSecret;

    public function __construct(?TicketProvider $provider = null)
    {
        if ($provider) {
            $this->setProvider($provider);
        }
    }

    public function configMapping(): array
    {
        return [
            'apikey' => (object)[
                'name' => 'API Key',
                'validation' => 'required|string',
            ],
            'webhook_secret' => (object)[
                'name' => 'Webhook Secret',
                'validation' => 'required|string',
            ],
        ];
    }

    public function install(): TicketProvider
    {
        $provider = TicketProvider::whereCode($this->code)->first();
        if (!$provider) {
            $provider = new TicketProvider();
            $provider->name = $this->name;
            $provider->code = $this->code;
            $provider->provider_class = get_called_class();
            $provider->enabled = false;
            $provider->save();
        }
        return $provider;
    }

    protected function setProvider(TicketProvider $provider): void
    {
        $this->provider = $provider;
        $this->apikey = $this->provider->apikey;
        $this->webhookSecret = $this->provider->webhook_secret ?? '';
    }

    public function processWebhook(Request $request): bool
    {
        return true;
    }

    public function syncTicket(string $id): ?Ticket
    {
        return null;
    }

    public function syncTickets(EmailAddress $email): void
    {
    }
}
