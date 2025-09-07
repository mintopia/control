<?php

namespace Tests\Unit\app\Services\TicketProviders\HelperClasses;

use App\Models\Ticket;
use App\Models\TicketProvider;
use App\Models\User;
use App\Services\TicketProviders\Traits\GenericSyncAllTrait;
use Mockery\MockInterface;

class DummyProviderWithSyncAll
{
    use GenericSyncAllTrait;

    public ?TicketProvider $provider;
    public $makeTicketCalled = false;
    public $makeTicketArgs = [];

    public function __construct(?TicketProvider $provider = null)
    {
        $this->provider = $provider;
    }

    public function getTickets(?string $address = null): array
    {
        return [];
    }

    protected function makeTicket(?User $user, object $data): ?Ticket
    {
        $this->makeTicketCalled = true;
        $this->makeTicketArgs[] = [$user, $data];

        return new Ticket([
            'user_id' => $user->id ?? 1,
        ]);
    }
}
