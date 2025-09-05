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
        $result = $c->add_discord();
        $this->assertEquals('added', $result);
    }

    public function testAddDiscordReturnSuccessAndFailure()
    {
        Setting::create(['code' => 'discord.server.name', 'name' => 'DName', 'value' => null, 'hidden' => false, 'type' => 0]);
        Setting::create(['code' => 'discord.server.id', 'name' => 'DId', 'value' => null, 'hidden' => false, 'type' => 0]);

        // create the discord provider record and point it at our dummy provider
        SocialProvider::create(['code' => 'discord', 'name' => 'Discord', 'provider_class' => DummyDiscordProvider::class, 'supports_auth' => 0, 'enabled' => 1, 'auth_enabled' => 0, 'can_be_renamed' => 0]);

        // register the named routes used by the controller to avoid UrlGenerationException
        $this->app['router']->get('/discord-return', fn() => '')->name('admin.settings.discord_return');
        $this->app['router']->get('/settings', fn() => '')->name('admin.settings.index');

        $c = new SettingController();
        $resp = $c->add_discord_return();
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $resp);

        // settings should be populated
        $this->assertNotNull(Setting::whereCode('discord.server.name')->first()->value);
        $this->assertNotNull(Setting::whereCode('discord.server.id')->first()->value);

        // Now simulate failure by updating provider_class to a throwing stub
        SocialProvider::whereCode('discord')->update(['provider_class' => ThrowingDiscordProvider::class]);
        $resp2 = $c->add_discord_return();
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $resp2);

        // settings should be cleared on failure
        $this->assertNull(Setting::whereCode('discord.server.name')->first()->value);
        $this->assertNull(Setting::whereCode('discord.server.id')->first()->value);
    }
}
