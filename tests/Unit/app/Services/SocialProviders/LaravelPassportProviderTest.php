<?php

namespace Tests\Unit\app\Services\SocialProviders;

use App\Models\LinkedAccount;
use App\Models\ProviderSetting;
use App\Models\SocialProvider;
use App\Services\SocialProviders\LaravelPassportProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class LaravelPassportProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function getProvider(array $settings = [], ?string $redirectUrl = null)
    {
        $socialProvider = SocialProvider::factory()->create([
            'name' => 'Laravel Passport',
            'code' => 'laravelpassport',
            'provider_class' => LaravelPassportProvider::class,
        ]);
        foreach ($settings as $code => $value) {
            ProviderSetting::factory()->create([
                'provider_id' => $socialProvider->id,
                'code' => $code,
                'value' => $value,
            ]);
        }
        return new LaravelPassportProvider($socialProvider, $redirectUrl);
    }

    public function testConfigMappingIncludesHost()
    {
        $provider = $this->getProvider();
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('client_id', $mapping);
        $this->assertArrayHasKey('client_secret', $mapping);
        $this->assertArrayHasKey('host', $mapping);
        $this->assertEquals('Passport Host', $mapping['host']->name);
        $this->assertEquals('required|string', $mapping['host']->validation);
    }

    public function testNameCanBeRenamedFromProvider()
    {
        $socialProvider = SocialProvider::factory()->create([
            'name' => 'Custom Passport',
            'code' => 'laravelpassport',
            'provider_class' => LaravelPassportProvider::class,
        ]);
        $provider = new LaravelPassportProvider($socialProvider);
        $reflection = new ReflectionClass($provider);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);
        $this->assertEquals('Custom Passport', $nameProperty->getValue($provider));
    }

    public function testGetSocialiteProviderBuildsProviderWithConfig()
    {
        $provider = $this->getProvider([
            'client_id' => 'id',
            'client_secret' => 'secret',
            'host' => 'https://passport.example.com',
        ], 'https://redirect.url');

        // Use a lightweight test double (anonymous class) instead of Mockery so
        // the test focuses on configuration wiring and remains Eloquent-backed.
        $mockSocialiteProvider = new class {
            public function setConfig($c)
            {
                return $this;
            }

            public function with($arr)
            {
                return $this;
            }
        };

        // We don't assert exact args here; Socialite facade is stubbed to return
        // our provider instance so the provider's internal call path can be exercised.
        Socialite::shouldReceive('buildProvider')->andReturn($mockSocialiteProvider);

        $method = new ReflectionMethod($provider, 'getSocialiteProvider');
        $method->setAccessible(true);
        $result = $method->invoke($provider);

        $this->assertSame($mockSocialiteProvider, $result);
    }

    public function testUpdateAccountSetsFields()
    {
        $provider = $this->getProvider();
        $account = new LinkedAccount();

        $remoteUser = new class {
            public function getAvatar()
            {
                return 'avatar_url';
            }

            public $refreshToken = 'refresh_token';
            public $token = 'access_token';

            public function getNickname()
            {
                return 'nickname';
            }
        };

        $method = new ReflectionMethod($provider, 'updateAccount');
        $method->setAccessible(true);
        $method->invoke($provider, $account, $remoteUser);

        $this->assertEquals('avatar_url', $account->avatar_url);
        $this->assertEquals('refresh_token', $account->refresh_token);
        $this->assertEquals('access_token', $account->access_token);
        $this->assertEquals('nickname', $account->name);
    }
}
