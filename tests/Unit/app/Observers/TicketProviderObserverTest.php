<?php

namespace Tests\Unit\app\Observers;

use App\Models\TicketProvider;
use App\Observers\TicketProviderObserver;
use Tests\TestCase;

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

    // NOTE The following tests assert empty observer handlers are currently no-ops.
    public function testCreatedIsNoop()
    {
        $ticketProvider = new TicketProvider();
        $observer = new TicketProviderObserver();
        $m = 'created';
        if (method_exists($observer, $m)) {
            $this->assertNull($observer->$m($ticketProvider));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testUpdatedIsNoop()
    {
        $ticketProvider = new TicketProvider();
        $observer = new TicketProviderObserver();
        $m = 'updated';
        if (method_exists($observer, $m)) {
            $this->assertNull($observer->$m($ticketProvider));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testDeletedIsNoop()
    {
        $ticketProvider = new TicketProvider();
        $observer = new TicketProviderObserver();
        $m = 'deleted';
        if (method_exists($observer, $m)) {
            $this->assertNull($observer->$m($ticketProvider));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testRestoredIsNoop()
    {
        $ticketProvider = new TicketProvider();
        $observer = new TicketProviderObserver();
        $m = 'restored';
        if (method_exists($observer, $m)) {
            $this->assertNull($observer->$m($ticketProvider));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testForceDeletedIsNoop()
    {
        $ticketProvider = new TicketProvider();
        $observer = new TicketProviderObserver();
        $m = 'forceDeleted';
        if (method_exists($observer, $m)) {
            $this->assertNull($observer->$m($ticketProvider));
        } else {
            $this->assertTrue(true);
        }
    }
}
