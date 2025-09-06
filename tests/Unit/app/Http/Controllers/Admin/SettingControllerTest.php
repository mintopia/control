<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\SettingController;
use App\Models\Setting;
use App\Models\SocialProvider;
use Illuminate\Http\RedirectResponse;

use App\Services\Contracts\SocialProviderContract;
use App\Models\SocialProvider as SocialProviderModel;

class DummyDiscordProvider implements SocialProviderContract
{
    public $provider;
    public $redirectUrl;
    public function __construct($provider = null, $redirectUrl = null)
    {
        $this->provider = $provider;
        $this->redirectUrl = $redirectUrl;
    }
    public function configMapping(): array
    {
        return [];
    }
    public function install(): SocialProviderModel
    {
        return $this->provider;
    }
    public function redirect(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->to('/');
    }
    public function user(?\App\Models\User $localUser = null)
    {
        return null;
    }
    public function addBotToServer()
    {
        return 'added';
    }
    public function bot()
    {
        return (object)['accessTokenResponseBody' => ['guild' => ['name' => 'G1', 'id' => '123']]];
    }
}

class ThrowingDiscordProvider implements SocialProviderContract
{
    public $provider;
    public $redirectUrl;
    public function __construct($provider = null, $redirectUrl = null)
    {
        $this->provider = $provider;
        $this->redirectUrl = $redirectUrl;
    }
    public function configMapping(): array
    {
        return [];
    }
    public function install(): SocialProviderModel
    {
        return $this->provider;
    }
    public function redirect(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->to('/');
    }
    public function user(?\App\Models\User $localUser = null)
    {
        return null;
    }
    public function bot()
    {
        throw new \Exception('fail');
    }
}

// A small test-specific subclass to expose the protected getDiscordProvider for unit testing
class TestableSettingController extends SettingController
{
    public function callGetDiscordProvider(?string $redirectUrl = null)
    {
        return $this->getDiscordProvider($redirectUrl);
    }
}

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsView()
    {
        Setting::factory()->create(['code' => 'test', 'hidden' => false]);
        $c = new SettingController();
        $this->assertTrue(is_object($c->index()));
    }

    public function testUpdateSavesSettings()
    {
        $s = Setting::create(['code' => 'f1', 'name' => 'F1', 'value' => null, 'hidden' => false, 'type' => \App\Enums\SettingType::stBoolean]);
        $c = new SettingController();
        $req = \App\Http\Requests\Admin\SettingUpdateRequest::create('/', 'POST', ['f1' => '1']);
        $resp = $c->update($req);
        $this->assertInstanceOf(RedirectResponse::class, $resp);
        $this->assertDatabaseHas('settings', ['code' => 'f1', 'value' => 1]);
    }

    public function testAddDiscordCallsProvider()
    {
        $prov = SocialProvider::create(['code' => 'discord', 'name' => 'Discord', 'provider_class' => DummyDiscordProvider::class, 'supports_auth' => 0, 'enabled' => 1, 'auth_enabled' => 0, 'can_be_renamed' => 0]);

        $c = new SettingController();
        $result = $c->addDiscord();
        $this->assertEquals('added', $result);
    }

    public function testGetDiscordProviderReturnsConfiguredProvider()
    {
        // create the discord provider record and point it at our dummy provider
        SocialProvider::create(['code' => 'discord', 'name' => 'Discord', 'provider_class' => DummyDiscordProvider::class, 'supports_auth' => 0, 'enabled' => 1, 'auth_enabled' => 0, 'can_be_renamed' => 0]);

        // register the named route used by provider construction
        $this->app['router']->get('/discord-return', fn() => '')->name('admin.settings.discord_return');

        $controller = new TestableSettingController();
        $provider = $controller->callGetDiscordProvider();

        $this->assertIsObject($provider);
        $this->assertInstanceOf(\App\Services\Contracts\SocialProviderContract::class, $provider);
        // provider should have been constructed with the redirect URL we registered
        $this->assertEquals(route('admin.settings.discord_return'), $provider->redirectUrl ?? null);
    }

    public function testAddDiscordReturnWritesAndClearsSettings()
    {
        // Use factories to create the settings we expect to be updated
        Setting::factory()->create(['code' => 'discord.server.name', 'name' => 'DName', 'value' => null, 'hidden' => false]);
        Setting::factory()->create(['code' => 'discord.server.id', 'name' => 'DId', 'value' => null, 'hidden' => false]);

        // register routes used by the controller to avoid UrlGenerationException
        $this->app['router']->get('/discord-return', fn() => '')->name('admin.settings.discord_return');
        $this->app['router']->get('/settings', fn() => '')->name('admin.settings.index');

        // Create a controller partial mock that stubs getDiscordProvider() to return our dummy provider
        $dummy = new DummyDiscordProvider();
        $controllerMock = $this->getMockBuilder(SettingController::class)
            ->onlyMethods(['getDiscordProvider'])
            ->getMock();
        $controllerMock->method('getDiscordProvider')->willReturn($dummy);

        // Call the full controller action to ensure settings are written using the stub provider
        try {
            $fullResp = $controllerMock->addDiscordReturn();
        } catch (\Throwable $e) {
            $this->fail('Unexpected exception when calling add_discord_return with dummy provider: ' . $e->getMessage());
        }
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $fullResp, 'add_discord_return should return a redirect on success');
        $this->assertEquals('G1', Setting::whereCode('discord.server.name')->first()->value, 'discord.server.name should be set to guild name');
        $this->assertEquals('123', Setting::whereCode('discord.server.id')->first()->value, 'discord.server.id should be set to guild id');

        // Now simulate failure by using a controller mock that returns a throwing provider
        $throwing = new ThrowingDiscordProvider();
        $controllerMockFail = $this->getMockBuilder(SettingController::class)
            ->onlyMethods(['getDiscordProvider'])
            ->getMock();
        $controllerMockFail->method('getDiscordProvider')->willReturn($throwing);

        try {
            $fullResp2 = $controllerMockFail->addDiscordReturn();
        } catch (\Throwable $e) {
            $this->fail('Unexpected exception when calling add_discord_return with throwing provider: ' . $e->getMessage());
        }
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $fullResp2, 'add_discord_return should return a redirect on failure too');
        $this->assertNull(Setting::whereCode('discord.server.name')->first()->value, 'discord.server.name should be cleared on failure');
        $this->assertNull(Setting::whereCode('discord.server.id')->first()->value, 'discord.server.id should be cleared on failure');
    }
}
