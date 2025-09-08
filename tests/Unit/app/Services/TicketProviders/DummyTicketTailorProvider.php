<?php

namespace Tests\Unit\app\Services\TicketProviders;

use App\Models\Event;
use App\Models\EventMapping;
use App\Models\Ticket;
use App\Models\TicketProvider;
use App\Models\TicketType;
use App\Models\TicketTypeMapping;
use App\Models\User;
use App\Services\TicketProviders\TicketTailorProvider;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

/**
 * Test helper exposing protected methods of TicketTailorProvider as public wrappers.
 */
// @deprecated Use anonymous test doubles from Tests\Traits\ProviderTestHelpers and
// ReflectionHelpers::callProtected(...) instead. This file remains as a stub.
class DummyTicketTailorProvider extends TicketTailorProvider
{
    public function __construct(...$args)
    {
        trigger_error('DummyTicketTailorProvider is deprecated — use ProviderTestHelpers::makeTicketTailorProvider() and ReflectionHelpers::callProtected()', E_USER_DEPRECATED);
        parent::__construct($args[0] ?? null);
    }
}
