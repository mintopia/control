<?php

namespace Tests\Unit\app\Services\TicketProviders;

use App\Services\TicketProviders\InternalTicketProvider;
use ReflectionClass;
use Tests\TestCase;

class InternalTicketProviderTest extends TestCase
{
    public function testConfigMappingReturnsEmptyArray()
    {
        $provider = new InternalTicketProvider();
        $this->assertEquals([], $provider->configMapping());
    }
}
