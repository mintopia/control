<?php

namespace Tests\Unit\app\Services\TicketProviders\HelperClasses;

use App\Models\TicketProvider;
use App\Models\User;
use App\Services\TicketProviders\GenericTicketProvider;
use GuzzleHttp\Client;

/**
 * Test helper exposing protected methods of GenericTicketProvider as public wrappers.
 */
// @deprecated Use anonymous test doubles from Tests\Traits\ProviderTestHelpers and
// ReflectionHelpers::callProtected(...) instead. This file remains as a stub.
class DummyGenericTicketProvider extends GenericTicketProvider
{
    public function __construct(...$args)
    {
        trigger_error('DummyGenericTicketProvider is deprecated — use ProviderTestHelpers::makeTicketProvider() and ReflectionHelpers::callProtected()', E_USER_DEPRECATED);
        parent::__construct($args[0] ?? null);
    }
}
