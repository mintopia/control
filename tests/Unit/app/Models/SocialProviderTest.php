<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\SocialProvider;

class SocialProviderTest extends TestCase
{
    public function testCanInstantiateSocialProvider()
    {
        $provider = new SocialProvider();
        $this->assertInstanceOf(SocialProvider::class, $provider);
    }

    /** Test the accounts relationship.
     * FIXME Commented out as not working - TBC
     * Class MockObject_SocialProviderContract_3cb0ca9b contains 4 abstract methods and must therefore be declared abstract or implement the remaining methods
     * (App\Services\Contracts\SocialProviderContract::__construct, App\Services\Contracts\SocialProviderContract::configMapping, App\Services\Contracts\SocialProviderContract::install, ...)
     */
    /*
    public function testAccountsRelationship()
    {
        $provider = new SocialProvider();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $provider->accounts());
    }

    public function testSettingsRelationship()
    {
        $provider = new SocialProvider();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $provider->settings());
    }

    public function testGetProviderReturnsContract()
    {
        $mockContract = $this->getMockBuilder(\App\Services\Contracts\SocialProviderContract::class)
            ->disableOriginalConstructor()
            ->getMock();
        $provider = new SocialProvider();
        $provider->provider_class = get_class($mockContract);
        $result = $provider->getProvider();
        $this->assertInstanceOf(\App\Services\Contracts\SocialProviderContract::class, $result);
    }

    public function testRedirectDelegatesToProvider()
    {
        $mockProvider = $this->getMockBuilder(\App\Services\Contracts\SocialProviderContract::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['redirect'])
            ->getMock();
        $mockProvider->expects($this->once())->method('redirect')->willReturn('redirected');
        $provider = $this->getMockBuilder(SocialProvider::class)
            ->onlyMethods(['getProvider'])
            ->getMock();
        $provider->method('getProvider')->willReturn($mockProvider);
        $this->assertEquals('redirected', $provider->redirect());
    }

    public function testUserDelegatesToProvider()
    {
        $mockProvider = $this->getMockBuilder(\App\Services\Contracts\SocialProviderContract::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['user'])
            ->getMock();
        $mockProvider->expects($this->once())->method('user')->willReturn('user-object');
        $provider = $this->getMockBuilder(SocialProvider::class)
            ->onlyMethods(['getProvider'])
            ->getMock();
        $provider->method('getProvider')->willReturn($mockProvider);
        $this->assertEquals('user-object', $provider->user());
    }

    public function testConfigMappingDelegatesToProvider()
    {
        $mockProvider = $this->getMockBuilder(\App\Services\Contracts\SocialProviderContract::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['configMapping'])
            ->getMock();
        $mockProvider->expects($this->once())->method('configMapping')->willReturn(['foo' => 'bar']);
        $provider = $this->getMockBuilder(SocialProvider::class)
            ->onlyMethods(['getProvider'])
            ->getMock();
        $provider->method('getProvider')->willReturn($mockProvider);
        $this->assertEquals(['foo' => 'bar'], $provider->configMapping());
    }

    public function testGetSettingReturnsCachedValue()
    {
        $provider = $this->getMockBuilder(SocialProvider::class)
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
        $provider = $this->getMockBuilder(SocialProvider::class)
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
        $provider = $this->getMockBuilder(SocialProvider::class)
            ->onlyMethods(['settings'])
            ->getMock();
        $provider->method('settings')->willReturn($mockRelation);
        $this->assertEquals('baz', $provider->getSetting('foo'));
    }

    public function testToStringNameReturnsCode()
    {
        $provider = new SocialProvider();
        $provider->code = 'test_code';
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $this->assertEquals('test_code', $method->invoke($provider));
    }
    */
}
