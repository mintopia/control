<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Enums\SettingType;
use App\Http\Controllers\Admin\SocialProviderController;
use App\Http\Requests\Admin\SocialProviderUpdateRequest;
use App\Models\ProviderSetting;
use App\Models\SocialProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Tests\TestCase;

class SocialProviderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testEditReturnsObject()
    {
        $prov = SocialProvider::factory()->create(['supports_auth' => true, 'can_be_renamed' => true]);
        $c = new SocialProviderController();
        $resp = $c->edit($prov);
        $this->assertTrue(is_object($resp));
    }

    public function testUpdatePersistsSettingsAndProviderFields()
    {
        $prov = SocialProvider::factory()->create(['supports_auth' => true, 'can_be_renamed' => true]);
        $c = $this->app->make(SocialProviderController::class);

        // create provider setting via factory
        ProviderSetting::factory()->create([
            'provider_id' => $prov->id,
            'name' => 'Opt',
            'code' => 'opt1',
            'type' => SettingType::stBoolean,
            'value' => true,
        ]);

        $req = SocialProviderUpdateRequest::create('/', 'POST', ['enabled' => 0, 'auth_enabled' => 1, 'name' => 'New', 'opt1' => 0]);
        $resp = $c->update($req, $prov);
        $this->assertInstanceOf(RedirectResponse::class, $resp);
        $this->assertDatabaseHas('provider_settings', ['code' => 'opt1', 'value' => false]);
        $this->assertDatabaseHas('social_providers', ['id' => $prov->id, 'enabled' => 0, 'auth_enabled' => 1, 'name' => 'New']);
    }

    public function testBooleanSettingIsClearedWhenMissingFromRequest()
    {
        $prov = SocialProvider::factory()->create(['supports_auth' => true, 'can_be_renamed' => true]);
        $c = $this->app->make(SocialProviderController::class);

        // create provider setting that is boolean and initially true
        ProviderSetting::factory()->create([
            'provider_id' => $prov->id,
            'name' => 'AutoOpt',
            'code' => 'auto_opt',
            'type' => SettingType::stBoolean,
            'value' => true,
        ]);

        // build request that does NOT include 'auto_opt' so the elseif branch should run
        $req = SocialProviderUpdateRequest::create('/', 'POST', ['enabled' => 1, 'auth_enabled' => 0, 'name' => 'KeepName']);
        $resp = $c->update($req, $prov);

        $this->assertInstanceOf(RedirectResponse::class, $resp);
        $this->assertDatabaseHas('provider_settings', ['code' => 'auto_opt', 'value' => false]);
    }
}
