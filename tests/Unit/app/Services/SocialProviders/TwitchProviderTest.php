<?php

namespace Tests\Unit\app\Services\SocialProviders;

use App\Models\LinkedAccount;
use App\Models\ProviderSetting;
use App\Models\SocialProvider;
use App\Services\SocialProviders\TwitchProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use ReflectionMethod;
use SocialiteProviders\Twitch\Provider as TwitchSocialiteProvider;
use Tests\TestCase;

class TwitchProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function getProvider(array $settings = [], ?string $redirectUrl = null)
    {
        $socialProvider = SocialProvider::factory()->create([
            'name' => 'Twitch',
            'code' => 'twitch',
            'provider_class' => TwitchProvider::class,
        ]);
        foreach ($settings as $code => $value) {
            ProviderSetting::factory()->create([
                'provider_id' => $socialProvider->id,
                'code' => $code,
                'value' => $value,
            ]);
        }
        return new TwitchProvider($socialProvider, $redirectUrl);
    }

    public function testConfigMappingReturnsExpectedArray()
    {
        $provider = $this->getProvider();
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('client_id', $mapping);
        $this->assertArrayHasKey('client_secret', $mapping);
        $this->assertEquals('Client ID', $mapping['client_id']->name);
        $this->assertEquals('Client Secret', $mapping['client_secret']->name);
        $this->assertTrue($mapping['client_secret']->encrypted);
    }

    public function testGetSocialiteProviderBuildsProviderWithConfig()
    {
        $provider = $this->getProvider([
            'client_id' => 'id',
            'client_secret' => 'secret',
        ], 'https://redirect.url');
        $mockSocialiteProvider = Mockery::mock(TwitchSocialiteProvider::class);
        Socialite::shouldReceive('buildProvider')
            ->with(TwitchSocialiteProvider::class, Mockery::type('array'))
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
