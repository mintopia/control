<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\TicketType;

class TicketTypeTest extends TestCase
{
    public function testCanInstantiateTicketType()
    {
        $type = new TicketType();
        $this->assertInstanceOf(TicketType::class, $type);
    }
}
