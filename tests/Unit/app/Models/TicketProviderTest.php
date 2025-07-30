<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\TicketProvider;

class TicketProviderTest extends TestCase
{
    public function testCanInstantiateTicketProvider()
    {
        $provider = new TicketProvider();
        $this->assertInstanceOf(TicketProvider::class, $provider);
    }
}
