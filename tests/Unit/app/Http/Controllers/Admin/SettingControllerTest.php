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

    //CHECK Test fails, reason unknown. may need complete test refactor
    public function testAddDiscordReturnSuccessAndFailure()
    {
        Setting::create(['code' => 'discord.server.name', 'name' => 'DName', 'value' => null, 'hidden' => false, 'type' => 0]);
        Setting::create(['code' => 'discord.server.id', 'name' => 'DId', 'value' => null, 'hidden' => false, 'type' => 0]);

        // create the discord provider record and point it at our dummy provider
        SocialProvider::create(['code' => 'discord', 'name' => 'Discord', 'provider_class' => DummyDiscordProvider::class, 'supports_auth' => 0, 'enabled' => 1, 'auth_enabled' => 0, 'can_be_renamed' => 0]);

        $c = new SettingController();
        $resp = $c->add_discord_return();
        $this->assertTrue(method_exists($resp, 'getTargetUrl'));

        // Now simulate failure by updating provider_class to a throwing stub
        SocialProvider::whereCode('discord')->update(['provider_class' => ThrowingDiscordProvider::class]);
        $resp2 = $c->add_discord_return();
        $this->assertTrue(method_exists($resp2, 'getTargetUrl'));
    }
}
