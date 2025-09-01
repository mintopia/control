<?php

namespace Tests\Unit\app\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Tests\TestCase;

class EventServiceProviderTest extends TestCase
{
    public function testObserversPropertyIsArray()
    {
        $provider = new \app\Providers\EventServiceProvider(app());
        $this->assertIsArray($this->getProtectedProperty($provider, 'observers'));
    }

    public function testListenPropertyIsArray()
    {
        $provider = new \app\Providers\EventServiceProvider(app());
        $this->assertIsArray($this->getProtectedProperty($provider, 'listen'));
    }

    public function testShouldDiscoverEventsReturnsFalse()
    {
        $provider = new \app\Providers\EventServiceProvider(app());
        $this->assertFalse($provider->shouldDiscoverEvents());
    }

    private function getProtectedProperty($object, $property)
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }
}
