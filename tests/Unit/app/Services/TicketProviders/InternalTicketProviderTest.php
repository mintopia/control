<?php

namespace Tests\Unit\app\Services\TicketProviders;

use App\Services\TicketProviders\InternalTicketProvider;
use ReflectionClass;
use Tests\TestCase;

class InternalTicketProviderTest extends TestCase
{
    public function test_config_mapping_returns_empty_array()
    {
        $provider = new InternalTicketProvider();
        $this->assertEquals([], $provider->configMapping());
    }
}
