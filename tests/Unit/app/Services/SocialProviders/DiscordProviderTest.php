<?php

namespace Tests\Unit\app\Services\SocialProviders;

use Tests\TestCase;
use App\Services\SocialProviders\DiscordProvider;
use App\Models\SocialProvider;
use App\Models\ProviderSetting;
use App\Models\LinkedAccount;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Mockery;

class DiscordProviderTest extends TestCase
{
    // CHECK Seeding issue? - or other artisan issue: Cannot run artisan commands
    // protected function setUp(): void
    // {
    //     parent::setUp();

    //     // Ensure the test environment uses an in-memory SQLite database
    //     config(['database.default' => 'sqlite']);
    //     config(['database.connections.sqlite.database' => ':memory:']);

    //     // Run all migrations to ensure tables exist for tests
    //     $this->artisan('migrate')->run();
    // }

    // protected function getProvider(array $settings = [])
    // {
    //     $socialProvider = SocialProvider::factory()->create([
    //         'name' => 'Discord',
    //         'code' => 'discord',
    //         'provider_class' => DiscordProvider::class,
    //     ]);
    //     foreach ($settings as $code => $value) {
    //         ProviderSetting::factory()->create([
    //             'provider_id' => $socialProvider->id,
    //             'code' => $code,
    //             'value' => $value,
    //         ]);
    //     }
    //     return new DiscordProvider($socialProvider);
    // }

    // public function test_config_mapping_includes_token()
    // {
    //     $provider = $this->getProvider();
    //     $mapping = $provider->configMapping();

    //     $this->assertArrayHasKey('client_id', $mapping);
    //     $this->assertArrayHasKey('client_secret', $mapping);
    //     $this->assertArrayHasKey('token', $mapping);
    //     $this->assertEquals('Bot Token', $mapping['token']->name);
    //     $this->assertTrue($mapping['token']->encrypted);
    // }

    // public function test_get_socialite_provider_returns_provider_instance()
    // {
    //     $provider = $this->getProvider([
    //         'client_id' => 'id',
    //         'client_secret' => 'secret',
    //     ]);
    //     $mockSocialite = Mockery::mock();
    //     Socialite::shouldReceive('buildProvider')->once()->andReturn($mockSocialite);
    //     $method = new \ReflectionMethod($provider, 'getSocialiteProvider');
    //     $method->setAccessible(true);
    //     $this->assertSame($mockSocialite, $method->invoke($provider));
    // }

    // public function test_update_account_sets_fields()
    // {
    //     $provider = $this->getProvider();
    //     $account = new LinkedAccount();
    //     $remoteUser = (object)[
    //         'getAvatar' => fn() => 'avatar_url',
    //         'refreshToken' => 'refresh',
    //         'token' => 'access',
    //         'getNickname' => fn() => 'nickname',
    //     ];
    //     // Use a class to allow method calls
    //     $remoteUser = new class {
    //         public function getAvatar()
    //         {
    //             return 'avatar_url';
    //         }
    //         public $refreshToken = 'refresh';
    //         public $token = 'access';
    //         public function getNickname()
    //         {
    //             return 'nickname';
    //         }
    //     };
    //     $method = new \ReflectionMethod($provider, 'updateAccount');
    //     $method->setAccessible(true);
    //     $method->invoke($provider, $account, $remoteUser);

    //     $this->assertEquals('avatar_url', $account->avatar_url);
    //     $this->assertEquals('refresh', $account->refresh_token);
    //     $this->assertEquals('access', $account->access_token);
    //     $this->assertEquals('nickname', $account->name);
    // }

    // public function test_get_bot_provider_calls_socialite_with_scopes_and_permissions()
    // {
    //     $provider = $this->getProvider([
    //         'client_id' => 'id',
    //         'client_secret' => 'secret',
    //     ]);
    //     $mockSocialite = Mockery::mock();
    //     $mockSocialite->shouldReceive('scopes')->with(['email', 'identify', 'bot'])->andReturnSelf();
    //     $mockSocialite->shouldReceive('with')->with(['permissions' => '268435456'])->andReturnSelf();
    //     Socialite::shouldReceive('buildProvider')->andReturn($mockSocialite);

    //     $method = new \ReflectionMethod($provider, 'getBotProvider');
    //     $method->setAccessible(true);
    //     $this->assertSame($mockSocialite, $method->invoke($provider));
    // }

    // public function test_add_bot_to_server_redirects()
    // {
    //     $provider = $this->getProvider([
    //         'client_id' => 'id',
    //         'client_secret' => 'secret',
    //     ]);
    //     $mockSocialite = Mockery::mock();
    //     $mockSocialite->shouldReceive('scopes')->andReturnSelf();
    //     $mockSocialite->shouldReceive('with')->andReturnSelf();
    //     $mockSocialite->shouldReceive('redirect')->andReturn(new RedirectResponse('/discord-bot-redirect'));
    //     Socialite::shouldReceive('buildProvider')->andReturn($mockSocialite);

    //     $response = $provider->addBotToServer();
    //     $this->assertInstanceOf(RedirectResponse::class, $response);
    //     $this->assertEquals('/discord-bot-redirect', $response->getTargetUrl());
    // }

    // public function test_bot_returns_user()
    // {
    //     $provider = $this->getProvider([
    //         'client_id' => 'id',
    //         'client_secret' => 'secret',
    //     ]);
    //     $mockSocialite = Mockery::mock();
    //     $mockSocialite->shouldReceive('scopes')->andReturnSelf();
    //     $mockSocialite->shouldReceive('with')->andReturnSelf();
    //     $mockSocialite->shouldReceive('user')->andReturn((object)['id' => 'bot-user']);
    //     Socialite::shouldReceive('buildProvider')->andReturn($mockSocialite);

    //     $user = $provider->bot();
    //     $this->assertEquals('bot-user', $user->id);
    // }
}
