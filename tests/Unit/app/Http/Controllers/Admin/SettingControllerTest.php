<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Enums\SettingType;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Requests\Admin\SettingUpdateRequest;
use App\Models\Setting;
use App\Models\SocialProvider;
use App\Services\Contracts\SocialProviderContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Tests\TestCase;
use Throwable;

// A small test-specific subclass to expose the protected getDiscordProvider for unit testing

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
        $s = Setting::create(['code' => 'f1', 'name' => 'F1', 'value' => null, 'hidden' => false, 'type' => SettingType::stBoolean]);
        $c = new SettingController();
        $req = SettingUpdateRequest::create('/', 'POST', ['f1' => '1']);
        $resp = $c->update($req);
        $this->assertInstanceOf(RedirectResponse::class, $resp);
        $this->assertDatabaseHas('settings', ['code' => 'f1', 'value' => 1]);
    }

    public function testAddDiscordCallsProvider()
    {
        $prov = SocialProvider::create(['code' => 'discord', 'name' => 'Discord', 'provider_class' => HelperClasses\DummyDiscordProvider::class, 'supports_auth' => 0, 'enabled' => 1, 'auth_enabled' => 0, 'can_be_renamed' => 0]);

        $c = new SettingController();
        $result = $c->addDiscord();
        $this->assertEquals('added', $result);
    }

    public function testGetDiscordProviderReturnsConfiguredProvider()
    {
        // create the discord provider record and point it at our dummy provider
        SocialProvider::create(['code' => 'discord', 'name' => 'Discord', 'provider_class' => HelperClasses\DummyDiscordProvider::class, 'supports_auth' => 0, 'enabled' => 1, 'auth_enabled' => 0, 'can_be_renamed' => 0]);

        // register the named route used by provider construction
        $this->app['router']->get('/discord-return', fn() => '')->name('admin.settings.discord_return');

        $controller = new HelperClasses\TestableSettingController();
        $provider = $controller->callGetDiscordProvider();

        $this->assertIsObject($provider);
        $this->assertInstanceOf(SocialProviderContract::class, $provider);
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
        $dummy = new HelperClasses\DummyDiscordProvider();
        $controllerMock = $this->getMockBuilder(SettingController::class)
            ->onlyMethods(['getDiscordProvider'])
            ->getMock();
        $controllerMock->method('getDiscordProvider')->willReturn($dummy);

        // Call the full controller action to ensure settings are written using the stub provider
        try {
            $fullResp = $controllerMock->addDiscordReturn();
        } catch (Throwable $e) {
            $this->fail('Unexpected exception when calling add_discord_return with dummy provider: ' . $e->getMessage());
        }
        $this->assertInstanceOf(RedirectResponse::class, $fullResp, 'add_discord_return should return a redirect on success');
        $this->assertEquals('G1', Setting::whereCode('discord.server.name')->first()->value, 'discord.server.name should be set to guild name');
        $this->assertEquals('123', Setting::whereCode('discord.server.id')->first()->value, 'discord.server.id should be set to guild id');

        // Now simulate failure by using a controller mock that returns a throwing provider
        $throwing = new HelperClasses\ThrowingDiscordProvider();
        $controllerMockFail = $this->getMockBuilder(SettingController::class)
            ->onlyMethods(['getDiscordProvider'])
            ->getMock();
        $controllerMockFail->method('getDiscordProvider')->willReturn($throwing);

        try {
            $fullResp2 = $controllerMockFail->addDiscordReturn();
        } catch (Throwable $e) {
            $this->fail('Unexpected exception when calling add_discord_return with throwing provider: ' . $e->getMessage());
        }
        $this->assertInstanceOf(RedirectResponse::class, $fullResp2, 'add_discord_return should return a redirect on failure too');
        $this->assertNull(Setting::whereCode('discord.server.name')->first()->value, 'discord.server.name should be cleared on failure');
        $this->assertNull(Setting::whereCode('discord.server.id')->first()->value, 'discord.server.id should be cleared on failure');
    }
}
