<?php

namespace Tests\Unit\app\Models;

use App\Models\TicketTypeMapping;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class TicketTypeMappingTest extends TestCase
{
    public function testCanInstantiateTicketTypeMapping()
    {
        $mapping = new TicketTypeMapping();
        $this->assertInstanceOf(TicketTypeMapping::class, $mapping);
    }

    public function testProviderRelationship()
    {
        $mapping = new TicketTypeMapping();
        $this->assertInstanceOf(BelongsTo::class, $mapping->provider());
    }

    public function testTypeRelationship()
    {
        $mapping = new TicketTypeMapping();
        $this->assertInstanceOf(BelongsTo::class, $mapping->type());
    }
}
