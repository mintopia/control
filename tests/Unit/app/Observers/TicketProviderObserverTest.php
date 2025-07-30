<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\TicketProviderObserver;
use App\Models\TicketProvider;

class TicketProviderObserverTest extends TestCase
{
    public function testSavingSetsCachePrefixIfMissing()
    {
        $ticketProvider = new TicketProvider();
        $ticketProvider->cache_prefix = null;
        $observer = new TicketProviderObserver();
        $observer->saving($ticketProvider);
        $this->assertNotNull($ticketProvider->cache_prefix);
    }
}
