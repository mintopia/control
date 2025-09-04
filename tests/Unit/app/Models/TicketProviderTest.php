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

    public function testTicketsRelationship()
    {
        $provider = new TicketProvider();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $provider->tickets());
    }

    public function testEventsRelationship()
    {
        $provider = new TicketProvider();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $provider->events());
    }

    public function testTypesRelationship()
    {
        $provider = new TicketProvider();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $provider->types());
    }

    public function testSettingsRelationship()
    {
        $provider = new TicketProvider();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $provider->settings());
    }

    // TEST getProvider
    // public function testGetProviderReturnsContract()
    // {
    //     $mock = $this->createMock(\App\Services\Contracts\TicketProviderContract::class);
    //     // bind the mock instance into the container so app()->make() returns it
    //     app()->instance(\App\Services\Contracts\TicketProviderContract::class, $mock);
    //     $provider = new TicketProvider();
    //     // point provider_class at the interface so the container returns our bound mock
    //     $provider->provider_class = \App\Services\Contracts\TicketProviderContract::class;
    //     $result = $provider->getProvider();
    //     $this->assertSame($mock, $result);
    //     $this->assertInstanceOf(\App\Services\Contracts\TicketProviderContract::class, $result);
    // }

    // public function testSyncTicketsDelegatesToProvider()
    // {
    //     $mockProvider = $this->createMock(\App\Services\Contracts\TicketProviderContract::class);
    //     $mockProvider->expects($this->once())->method('syncTickets')->with('test@example.com');
    //     $provider = $this->getMockBuilder(TicketProvider::class)
    //         ->onlyMethods(['getProvider'])
    //         ->getMock();
    //     $provider->method('getProvider')->willReturn($mockProvider);
    //     $provider->syncTickets('test@example.com');
    // }

    // public function testProcessWebhookDelegatesToProvider()
    // {
    //     $mockProvider = $this->createMock(\App\Services\Contracts\TicketProviderContract::class);
    //     $mockProvider->expects($this->once())->method('processWebhook')->willReturn(true);
    //     $provider = $this->getMockBuilder(TicketProvider::class)
    //         ->onlyMethods(['getProvider'])
    //         ->getMock();
    //     $provider->method('getProvider')->willReturn($mockProvider);
    //     $mockRequest = $this->createMock(\Illuminate\Http\Request::class);
    //     $this->assertTrue($provider->processWebhook($mockRequest));
    // }

    public function testGetEventsReturnsEmptyIfNotEnabled()
    {
        $provider = new TicketProvider();
        $provider->enabled = false;
        $this->assertEquals([], $provider->getEvents());
    }

    // public function testGetEventsReturnsDataIfEnabled()
    // {
    //     $mockProvider = $this->getMockBuilder(\App\Services\Contracts\TicketProviderContract::class)
    //         ->disableOriginalConstructor()
    //         ->onlyMethods(['getEvents'])
    //         ->getMock();
    //     $mockProvider->method('getEvents')->willReturn(['1' => 'Event 1']);
    //     $mockEvent = new class {
    //         public $external_id = '1';
    //     };
    //     $mockCollection = collect([$mockEvent]);
    //     $provider = $this->getMockBuilder(TicketProvider::class)
    //         ->onlyMethods(['getProvider', 'getAttribute'])
    //         ->getMock();
    //     $provider->enabled = true;
    //     $provider->method('getProvider')->willReturn($mockProvider);
    //     $provider->method('getAttribute')->with('events')->willReturn($mockCollection);
    //     $provider->setRelation('events', $mockCollection);
    //     $result = $provider->getEvents();
    //     $this->assertCount(1, $result);
    //     $this->assertEquals('1', $result[0]->id);
    //     $this->assertEquals('Event 1', $result[0]->name);
    // }

    public function testGetTicketTypesReturnsEmptyIfNotEnabled()
    {
        $provider = new TicketProvider();
        $provider->enabled = false;
        $mockEvent = $this->createMock(\App\Models\Event::class);
        $this->assertEquals([], $provider->getTicketTypes($mockEvent));
    }

    public function testGetSettingReturnsCachedValue()
    {
        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['settings'])
            ->getMock();
        // set the protected property properly via reflection so the class sees it
        $rp = new \ReflectionProperty(TicketProvider::class, '_settings');
        $rp->setAccessible(true);
        $rp->setValue($provider, ['foo' => 'bar']);
        $this->assertEquals('bar', $provider->getSetting('foo'));
    }

    //CHECK tests: Trying to configure method "whereCode" which cannot be configured because it does not exist, has not been specified, is final, or is static
    // public function testGetSettingReturnsNullIfNotFound()
    // {
    //     // Use a MorphMany mock instance so the return type matches the model signature
    //     $mockRelation = $this->getMockBuilder(\Illuminate\Database\Eloquent\Relations\MorphMany::class)
    //         ->disableOriginalConstructor()
    //         ->getMock();
    //     $mockRelation->method('whereCode')->willReturnSelf();
    //     $mockRelation->method('first')->willReturn(null);
    //     $provider = $this->getMockBuilder(TicketProvider::class)
    //         ->onlyMethods(['settings'])
    //         ->getMock();
    //     $provider->method('settings')->willReturn($mockRelation);
    //     $this->assertNull($provider->getSetting('notfound'));
    // }

    // public function testGetSettingReturnsValueFromRelation()
    // {
    //     $mockSetting = new class {
    //         public $value = 'baz';
    //     };
    //     // morph relation mock returning a setting instance
    //     $mockRelation = $this->getMockBuilder(\Illuminate\Database\Eloquent\Relations\MorphMany::class)
    //         ->disableOriginalConstructor()
    //         ->getMock();
    //     $mockRelation->method('whereCode')->willReturnSelf();
    //     $mockRelation->method('first')->willReturn($mockSetting);
    //     $provider = $this->getMockBuilder(TicketProvider::class)
    //         ->onlyMethods(['settings'])
    //         ->getMock();
    //     $provider->method('settings')->willReturn($mockRelation);
    //     $this->assertEquals('baz', $provider->getSetting('foo'));
    // }

    public function testClearCacheSetsCachePrefixAndSaves()
    {
        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['save'])
            ->getMock();
        $provider->expects($this->once())->method('save');
        $provider->clearCache();
        $this->assertNotEmpty($provider->cache_prefix);
    }

    public function testConfigMappingDelegatesToProvider()
    {
        $mockProvider = $this->createMock(\App\Services\Contracts\TicketProviderContract::class);
        $mockProvider->expects($this->once())->method('configMapping')->willReturn(['foo' => 'bar']);
        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['getProvider'])
            ->getMock();
        $provider->method('getProvider')->willReturn($mockProvider);
        $this->assertEquals(['foo' => 'bar'], $provider->configMapping());
    }

    public function testToStringNameReturnsCode()
    {
        $provider = new TicketProvider();
        $provider->code = 'test_code';
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $this->assertEquals('test_code', $method->invoke($provider));
    }
}
