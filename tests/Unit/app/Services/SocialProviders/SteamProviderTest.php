<?php

namespace Tests\Unit\app\Services\SocialProviders;

use Tests\TestCase;
use App\Services\SocialProviders\SteamProvider;
use App\Models\SocialProvider;
use App\Models\ProviderSetting;
use App\Models\LinkedAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Steam\Provider as SteamSocialiteProvider;
use Illuminate\Http\Request;
use Mockery;

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

    public function test_config_mapping_returns_expected_array()
    {
        $provider = $this->getProvider();
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('client_secret', $mapping);
        $this->assertEquals('API Key', $mapping['client_secret']->name);
        $this->assertEquals('required|string', $mapping['client_secret']->validation);
        $this->assertTrue($mapping['client_secret']->encrypted);
    }

    public function test_get_socialite_provider_builds_provider_with_config()
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

        $method = new \ReflectionMethod($provider, 'getSocialiteProvider');
        $method->setAccessible(true);
        $result = $method->invoke($provider);

        $this->assertSame($mockSocialiteProvider, $result);
    }

    public function test_update_account_sets_fields()
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

        $method = new \ReflectionMethod($provider, 'updateAccount');
        $method->setAccessible(true);
        $method->invoke($provider, $account, $remoteUser);

        $this->assertEquals('avatar_url', $account->avatar_url);
        $this->assertEquals('nickname', $account->name);
    }
}
