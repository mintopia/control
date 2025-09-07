<?php

namespace Tests\Unit\app\Models;

use App\Models\ProviderSetting;
use App\Models\SocialProvider;
use App\Models\User;
use App\Services\Contracts\SocialProviderContract;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use ReflectionClass;
use ReflectionObject;
use Tests\TestCase;

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
        $this->assertInstanceOf(HasMany::class, $provider->accounts());
    }

    public function testSettingsRelationship()
    {
        $provider = new SocialProvider();
        $this->assertInstanceOf(MorphMany::class, $provider->settings());
    }

    public function testGetProviderReturnsContract()
    {
        // Use a simple concrete stub that satisfies the contract
        $stubClass = HelperClasses\TestSocialProviderStub::class;
        $provider = new SocialProvider();
        $provider->provider_class = $stubClass;
        $result = $provider->getProvider();
        $this->assertInstanceOf(SocialProviderContract::class, $result);
    }

    public function testGetProviderUsesBoundContainerInstanceWhenAvailable()
    {
        // Create a provider and bind a custom stub into the container under its class name
        $provider = new SocialProvider();
        $provider->provider_class = HelperClasses\TestSocialProviderStub::class;

        // Create a distinct stub instance and bind it so app()->bound(...) returns true
        $boundStub = new class implements SocialProviderContract {
            public function __construct(?SocialProvider $provider = null, ?string $redirectUrl = null)
            {
            }

            public function configMapping(): array
            {
                return [];
            }

            public function install(): SocialProvider
            {
                throw new Exception('not used');
            }

            public function redirect(): RedirectResponse
            {
                return new RedirectResponse('/bound');
            }

            public function user(?User $localUser = null)
            {
                return 'bound-user';
            }
        };

        // bind a factory so app()->make(...) returns our stub instance
        app()->bind(HelperClasses\TestSocialProviderStub::class, function () use ($boundStub) {
            return $boundStub;
        });

        $result = $provider->getProvider();
        // ensure the container binding exists and the returned object implements the contract
        $this->assertTrue(app()->bound(HelperClasses\TestSocialProviderStub::class));
        $this->assertInstanceOf(SocialProviderContract::class, $result);
        // verify behavior delegated to the bound instance (user returns our sentinel)
        $this->assertEquals('bound-user', $result->user());
    }

    public function testRedirectDelegatesToProvider()
    {
        $stub = new class implements SocialProviderContract {
            public function __construct(?SocialProvider $provider = null, ?string $redirectUrl = null)
            {
            }

            public function configMapping(): array
            {
                return [];
            }

            public function install(): SocialProvider
            {
                throw new Exception('not used');
            }

            public function redirect(): RedirectResponse
            {
                return new RedirectResponse('/stub-redirect');
            }

            public function user(?User $localUser = null)
            {
                return null;
            }
        };
        $provider = $this->getMockBuilder(SocialProvider::class)
            ->onlyMethods(['getProvider'])
            ->getMock();
        $provider->method('getProvider')->willReturn($stub);
        $response = $provider->redirect();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/stub-redirect', $response->getTargetUrl());
    }

    public function testUserDelegatesToProvider()
    {
        $stub = new class implements SocialProviderContract {
            public function __construct(?SocialProvider $provider = null, ?string $redirectUrl = null)
            {
            }

            public function configMapping(): array
            {
                return [];
            }

            public function install(): SocialProvider
            {
                throw new Exception('not used');
            }

            public function redirect(): RedirectResponse
            {
                return new RedirectResponse('/stub-redirect');
            }

            public function user(?User $localUser = null)
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
            public function __construct(?SocialProvider $provider = null, ?string $redirectUrl = null)
            {
            }

            public function configMapping(): array
            {
                return ['foo' => 'bar'];
            }

            public function install(): SocialProvider
            {
                throw new Exception('not used');
            }

            public function redirect(): RedirectResponse
            {
                return new RedirectResponse('/stub-redirect');
            }

            public function user(?User $localUser = null)
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
        $ref = new ReflectionObject($provider);
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
        $reflection = new ReflectionClass($provider);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $this->assertEquals('test_code', $method->invoke($provider));
    }
}

// Small concrete stub implementing the SocialProviderContract for tests.
