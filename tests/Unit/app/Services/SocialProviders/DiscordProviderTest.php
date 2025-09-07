<?php

namespace Tests\Unit\app\Services\SocialProviders;

use App\Models\LinkedAccount;
use App\Models\ProviderSetting;
use App\Models\SocialProvider;
use App\Services\SocialProviders\DiscordProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class DiscordProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function getProvider(array $settings = [])
    {
        $socialProvider = SocialProvider::factory()->create([
            'name' => 'Discord',
            'code' => 'discord',
            'provider_class' => DiscordProvider::class,
        ]);
        foreach ($settings as $code => $value) {
            ProviderSetting::factory()->create([
                'provider_id' => $socialProvider->id,
                'code' => $code,
                'value' => $value,
            ]);
        }
        return new DiscordProvider($socialProvider);
    }

    public function testConfigMappingIncludesToken()
    {
        $provider = $this->getProvider();
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('client_id', $mapping);
        $this->assertArrayHasKey('client_secret', $mapping);
        $this->assertArrayHasKey('token', $mapping);
        $this->assertEquals('Bot Token', $mapping['token']->name);
        $this->assertTrue($mapping['token']->encrypted);
    }

    public function testGetSocialiteProviderReturnsProviderInstance()
    {
        $provider = $this->getProvider([
            'client_id' => 'id',
            'client_secret' => 'secret',
        ]);
        $mockSocialite = Mockery::mock();
        Socialite::shouldReceive('buildProvider')->once()->andReturn($mockSocialite);
        $method = new ReflectionMethod($provider, 'getSocialiteProvider');
        $method->setAccessible(true);
        $this->assertSame($mockSocialite, $method->invoke($provider));
    }

    public function testUpdateAccountSetsFields()
    {
        $provider = $this->getProvider();
        $account = new LinkedAccount();
        $remoteUser = (object)[
            'getAvatar' => fn() => 'avatar_url',
            'refreshToken' => 'refresh',
            'token' => 'access',
            'getNickname' => fn() => 'nickname',
        ];
        // Use a class to allow method calls
        $remoteUser = new class {
            public function getAvatar()
            {
                return 'avatar_url';
            }

            public $refreshToken = 'refresh';
            public $token = 'access';

            public function getNickname()
            {
                return 'nickname';
            }
        };
        $method = new ReflectionMethod($provider, 'updateAccount');
        $method->setAccessible(true);
        $method->invoke($provider, $account, $remoteUser);

        $this->assertEquals('avatar_url', $account->avatar_url);
        $this->assertEquals('refresh', $account->refresh_token);
        $this->assertEquals('access', $account->access_token);
        $this->assertEquals('nickname', $account->name);
    }

    public function testGetBotProviderCallsSocialiteWithScopesAndPermissions()
    {
        $provider = $this->getProvider([
            'client_id' => 'id',
            'client_secret' => 'secret',
        ]);
        $mockSocialite = Mockery::mock();
        $mockSocialite->shouldReceive('scopes')->with(['email', 'identify', 'bot'])->andReturnSelf();
        $mockSocialite->shouldReceive('with')->with(['permissions' => '268435456'])->andReturnSelf();
        Socialite::shouldReceive('buildProvider')->andReturn($mockSocialite);

        $method = new ReflectionMethod($provider, 'getBotProvider');
        $method->setAccessible(true);
        $this->assertSame($mockSocialite, $method->invoke($provider));
    }

    public function testAddBotToServerRedirects()
    {
        $provider = $this->getProvider([
            'client_id' => 'id',
            'client_secret' => 'secret',
        ]);
        $mockSocialite = Mockery::mock();
        $mockSocialite->shouldReceive('scopes')->andReturnSelf();
        $mockSocialite->shouldReceive('with')->andReturnSelf();
        $mockSocialite->shouldReceive('redirect')->andReturn(new RedirectResponse('/discord-bot-redirect'));
        Socialite::shouldReceive('buildProvider')->andReturn($mockSocialite);

        $response = $provider->addBotToServer();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/discord-bot-redirect', $response->getTargetUrl());
    }

    public function testBotReturnsUser()
    {
        $provider = $this->getProvider([
            'client_id' => 'id',
            'client_secret' => 'secret',
        ]);
        $mockSocialite = Mockery::mock();
        $mockSocialite->shouldReceive('scopes')->andReturnSelf();
        $mockSocialite->shouldReceive('with')->andReturnSelf();
        $mockSocialite->shouldReceive('user')->andReturn((object)['id' => 'bot-user']);
        Socialite::shouldReceive('buildProvider')->andReturn($mockSocialite);

        $user = $provider->bot();
        $this->assertEquals('bot-user', $user->id);
    }
}
