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

    /*
        * Test the getProvider method returns an instance of TicketProviderContract.
        * This test is commented out as it requires a valid contract implementation.
        * Uncomment and implement the contract to use this test.
    */
    /*
        * Class MockObject_TicketProviderContract_9c7b2a5f contains 7 abstract methods and must therefore be declared
        * abstract or implement the remaining methods (App\Services\Contracts\TicketProviderContract::__construct,
        * App\Services\Contracts\TicketProviderContract::configMapping, App\Services\Contracts\TicketProviderContract::install, ...)

    public function testGetProviderReturnsContract()
    {
        $mockContract = $this->getMockBuilder(\App\Services\Contracts\TicketProviderContract::class)
            ->disableOriginalConstructor()
            ->getMock();
        $provider = new TicketProvider();
        $provider->provider_class = get_class($mockContract);
        $result = $provider->getProvider();
        $this->assertInstanceOf(\App\Services\Contracts\TicketProviderContract::class, $result);
    }

    public function testSyncTicketsDelegatesToProvider()
    {
        $mockProvider = $this->getMockBuilder(\App\Services\Contracts\TicketProviderContract::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['syncTickets'])
            ->getMock();
        $mockProvider->expects($this->once())->method('syncTickets')->with('test@example.com');
        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['getProvider'])
            ->getMock();
        $provider->method('getProvider')->willReturn($mockProvider);
        $provider->syncTickets('test@example.com');
    }

    public function testProcessWebhookDelegatesToProvider()
    {
        $mockProvider = $this->getMockBuilder(\App\Services\Contracts\TicketProviderContract::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['processWebhook'])
            ->getMock();
        $mockProvider->expects($this->once())->method('processWebhook')->willReturn(true);
        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['getProvider'])
            ->getMock();
        $provider->method('getProvider')->willReturn($mockProvider);
        $mockRequest = $this->createMock(\Illuminate\Http\Request::class);
        $this->assertTrue($provider->processWebhook($mockRequest));
    }

    public function testGetEventsReturnsEmptyIfNotEnabled()
    {
        $provider = new TicketProvider();
        $provider->enabled = false;
        $this->assertEquals([], $provider->getEvents());
    }

    public function testGetEventsReturnsDataIfEnabled()
    {
        $mockProvider = $this->getMockBuilder(\App\Services\Contracts\TicketProviderContract::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEvents'])
            ->getMock();
        $mockProvider->method('getEvents')->willReturn(['1' => 'Event 1']);
        $mockEvent = new class {
            public $external_id = '1';
        };
        $mockCollection = collect([$mockEvent]);
        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['getProvider', 'getAttribute'])
            ->getMock();
        $provider->enabled = true;
        $provider->method('getProvider')->willReturn($mockProvider);
        $provider->method('getAttribute')->with('events')->willReturn($mockCollection);
        $provider->setRelation('events', $mockCollection);
        $result = $provider->getEvents();
        $this->assertCount(1, $result);
        $this->assertEquals('1', $result[0]->id);
        $this->assertEquals('Event 1', $result[0]->name);
    }

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
        $provider->_settings = ['foo' => 'bar'];
        $this->assertEquals('bar', $provider->getSetting('foo'));
    }

    public function testGetSettingReturnsNullIfNotFound()
    {
        $mockRelation = $this->getMockBuilder(\Illuminate\Database\Eloquent\Relations\MorphMany::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['whereCode', 'first'])
            ->getMock();
        $mockRelation->method('whereCode')->willReturnSelf();
        $mockRelation->method('first')->willReturn(null);
        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['settings'])
            ->getMock();
        $provider->method('settings')->willReturn($mockRelation);
        $this->assertNull($provider->getSetting('notfound'));
    }

    public function testGetSettingReturnsValueFromRelation()
    {
        $mockSetting = new class {
            public $value = 'baz';
        };
        $mockRelation = $this->getMockBuilder(\Illuminate\Database\Eloquent\Relations\MorphMany::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['whereCode', 'first'])
            ->getMock();
        $mockRelation->method('whereCode')->willReturnSelf();
        $mockRelation->method('first')->willReturn($mockSetting);
        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['settings'])
            ->getMock();
        $provider->method('settings')->willReturn($mockRelation);
        $this->assertEquals('baz', $provider->getSetting('foo'));
    }

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
        $mockProvider = $this->getMockBuilder(\App\Services\Contracts\TicketProviderContract::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['configMapping'])
            ->getMock();
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
    */
}
