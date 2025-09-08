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
// @deprecated Use anonymous test doubles from Tests\Traits\ProviderTestHelpers and
// ReflectionHelpers::callProtected(...) instead. This file remains as a stub.
class DummyWooCommerceProvider extends WooCommerceProvider
{
    public function __construct(...$args)
    {
        trigger_error('DummyWooCommerceProvider is deprecated — use ProviderTestHelpers::makeWooCommerceProvider() and ReflectionHelpers::callProtected()', E_USER_DEPRECATED);
        parent::__construct($args[0] ?? null);
    }
}
