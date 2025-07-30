<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\TicketTypeMapping;

class TicketTypeMappingTest extends TestCase
{
    public function testCanInstantiateTicketTypeMapping()
    {
        $mapping = new TicketTypeMapping();
        $this->assertInstanceOf(TicketTypeMapping::class, $mapping);
    }
}
