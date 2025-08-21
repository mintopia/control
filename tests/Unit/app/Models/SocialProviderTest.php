<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\SocialProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Contracts\SocialProviderContract;
use App\Models\ProviderSetting;

class SocialProviderTest extends TestCase
{
    use RefreshDatabase;
    public function testCanInstantiateSocialProvider()
    {
        $provider = new SocialProvider();
        $this->assertInstanceOf(SocialProvider::class, $provider);
    }

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
        // Use a simple concrete stub that satisfies the contract
        $stubClass = TestSocialProviderStub::class;
        $provider = new SocialProvider();
        $provider->provider_class = $stubClass;
        $result = $provider->getProvider();
        $this->assertInstanceOf(SocialProviderContract::class, $result);
    }

    public function testRedirectDelegatesToProvider()
    {
        $stub = new class implements SocialProviderContract {
            public function __construct(?\App\Models\SocialProvider $provider = null, ?string $redirectUrl = null) {}
            public function configMapping(): array
            {
                return [];
            }
            public function install(): \App\Models\SocialProvider
            {
                throw new \Exception('not used');
            }
            public function redirect(): \Illuminate\Http\RedirectResponse
            {
                return new \Illuminate\Http\RedirectResponse('/stub-redirect');
            }
            public function user(?\App\Models\User $localUser = null)
            {
                return null;
            }
        };
        $provider = $this->getMockBuilder(SocialProvider::class)
            ->onlyMethods(['getProvider'])
            ->getMock();
        $provider->method('getProvider')->willReturn($stub);
        $response = $provider->redirect();
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals('/stub-redirect', $response->getTargetUrl());
    }

    public function testUserDelegatesToProvider()
    {
        $stub = new class implements SocialProviderContract {
            public function __construct(?\App\Models\SocialProvider $provider = null, ?string $redirectUrl = null) {}
            public function configMapping(): array
            {
                return [];
            }
            public function install(): \App\Models\SocialProvider
            {
                throw new \Exception('not used');
            }
            public function redirect(): \Illuminate\Http\RedirectResponse
            {
                return new \Illuminate\Http\RedirectResponse('/stub-redirect');
            }
            public function user(?\App\Models\User $localUser = null)
            {
                return 'user-object';
            }
        };
        $provider = $this->getMockBuilder(SocialProvider::class)
            ->onlyMethods(['getProvider'])
            ->getMock();
        $provider->method('getProvider')->willReturn($stub);
        $this->assertEquals('user-object', $provider->user());
    }

    public function testConfigMappingDelegatesToProvider()
    {
        $stub = new class implements SocialProviderContract {
            public function __construct(?\App\Models\SocialProvider $provider = null, ?string $redirectUrl = null) {}
            public function configMapping(): array
            {
                return ['foo' => 'bar'];
            }
            public function install(): \App\Models\SocialProvider
            {
                throw new \Exception('not used');
            }
            public function redirect(): \Illuminate\Http\RedirectResponse
            {
                return new \Illuminate\Http\RedirectResponse('/stub-redirect');
            }
            public function user(?\App\Models\User $localUser = null)
            {
                return null;
            }
        };
        $provider = $this->getMockBuilder(SocialProvider::class)
            ->onlyMethods(['getProvider'])
            ->getMock();
        $provider->method('getProvider')->willReturn($stub);
        $this->assertEquals(['foo' => 'bar'], $provider->configMapping());
    }

    public function testGetSettingReturnsCachedValue()
    {
        $provider = new SocialProvider();
        // $_settings is a protected property on the model; set it via reflection so
        // getSetting() reads the cached value instead of a newly created public prop.
        $ref = new \ReflectionObject($provider);
        $prop = $ref->getProperty('_settings');
        $prop->setAccessible(true);
        $prop->setValue($provider, ['foo' => 'bar']);
        $this->assertEquals('bar', $provider->getSetting('foo'));
    }

    public function testGetSettingReturnsNullIfNotFound()
    {
        // Use a real provider and ensure the relation returns null when no setting exists
        $provider = SocialProvider::factory()->make();
        $this->assertNull($provider->getSetting('notfound'));
    }

    public function testGetSettingReturnsValueFromRelation()
    {
        $provider = SocialProvider::factory()->create();
        ProviderSetting::factory()->create([
            'provider_id' => $provider->id,
            'provider_type' => get_class($provider),
            'code' => 'foo',
            'value' => 'baz',
        ]);
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
}

// Small concrete stub implementing the SocialProviderContract for tests.
class TestSocialProviderStub implements SocialProviderContract
{
    public function __construct(?\App\Models\SocialProvider $provider = null, ?string $redirectUrl = null)
    {
        // no-op
    }

    public function configMapping(): array
    {
        return [];
    }

    public function install(): \App\Models\SocialProvider
    {
        throw new \Exception('Not implemented in test stub');
    }

    public function redirect(): \Illuminate\Http\RedirectResponse
    {
        return new \Illuminate\Http\RedirectResponse('/stub-redirect');
    }

    public function user(?\App\Models\User $localUser = null)
    {
        return null;
    }
}
