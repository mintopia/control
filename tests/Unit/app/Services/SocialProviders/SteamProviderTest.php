<?php

namespace Tests\Unit\app\Services\SocialProviders;

use App\Models\LinkedAccount;
use App\Models\ProviderSetting;
use App\Models\SocialProvider;
use App\Services\SocialProviders\SteamProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use ReflectionMethod;
use SocialiteProviders\Steam\Provider as SteamSocialiteProvider;
use Tests\TestCase;

class SteamProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function getProvider(array $settings = [], ?string $redirectUrl = null)
    {
        $socialProvider = SocialProvider::factory()->create([
            'name' => 'Steam',
            'code' => 'steam',
            'provider_class' => SteamProvider::class,
        ]);
        foreach ($settings as $code => $value) {
            ProviderSetting::factory()->create([
                'provider_id' => $socialProvider->id,
                'code' => $code,
                'value' => $value,
            ]);
        }
        return new SteamProvider($socialProvider, $redirectUrl);
    }

    public function testConfigMappingReturnsExpectedArray()
    {
        $provider = $this->getProvider();
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('client_secret', $mapping);
        $this->assertEquals('API Key', $mapping['client_secret']->name);
        $this->assertEquals('required|string', $mapping['client_secret']->validation);
        $this->assertTrue($mapping['client_secret']->encrypted);
    }

    public function testGetSocialiteProviderBuildsProviderWithConfig()
    {
        $provider = $this->getProvider(['client_secret' => 'secret-key'], 'https://redirect.url');
        $mockSocialiteProvider = Mockery::mock(SteamSocialiteProvider::class);
        $mockSocialiteProvider->shouldReceive('setConfig')->once()->andReturnSelf();

        // Mock request()->getHost()
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getHost')->andReturn('localhost');
        // allow other request methods to be called by the framework during the test
        $request->shouldIgnoreMissing();
        $this->app->instance('request', $request);

        Socialite::shouldReceive('buildProvider')
            ->with(SteamSocialiteProvider::class, Mockery::type('array'))
            ->andReturn($mockSocialiteProvider);

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

            public function getNickname()
            {
                return 'nickname';
            }
        };

        $method = new ReflectionMethod($provider, 'updateAccount');
        $method->setAccessible(true);
        $method->invoke($provider, $account, $remoteUser);

        $this->assertEquals('avatar_url', $account->avatar_url);
        $this->assertEquals('nickname', $account->name);
    }
}
