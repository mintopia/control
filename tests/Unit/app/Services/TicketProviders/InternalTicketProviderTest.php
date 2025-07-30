<?php

namespace Tests\Unit\app\Services\TicketProviders;

use Tests\TestCase;
use App\Services\TicketProviders\InternalTicketProvider;

class InternalTicketProviderTest extends TestCase
{
    public function test_config_mapping_returns_empty_array()
    {
        $provider = new InternalTicketProvider();
        $this->assertEquals([], $provider->configMapping());
    }

    public function test_provider_code_and_name_are_correct()
    {
        $provider = new InternalTicketProvider();
        $reflection = new \ReflectionClass($provider);

        $codeProperty = $reflection->getProperty('code');
        $codeProperty->setAccessible(true);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);

        $this->assertEquals('internal', $codeProperty->getValue($provider));
        $this->assertEquals('Internal', $nameProperty->getValue($provider));
    }
}
