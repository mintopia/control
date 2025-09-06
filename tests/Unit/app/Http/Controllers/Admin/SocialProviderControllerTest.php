<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\SocialProviderController;
use App\Models\SocialProvider;
use App\Models\ProviderSetting;
use Illuminate\Http\RedirectResponse;

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
        $c = new SocialProviderController();

        // create provider settings
        $s1 = new ProviderSetting();
        $s1->provider()->associate($prov);
        $s1->name = 'Opt';
        $s1->code = 'opt1';
        $s1->type = \App\Enums\SettingType::stBoolean;
        $s1->value = true;
        $s1->save();

        $req = \App\Http\Requests\Admin\SocialProviderUpdateRequest::create('/', 'POST', ['enabled' => 0, 'auth_enabled' => 1, 'name' => 'New', 'opt1' => 0]);
        $resp = $c->update($req, $prov);
        $this->assertInstanceOf(RedirectResponse::class, $resp);
        $this->assertDatabaseHas('provider_settings', ['code' => 'opt1', 'value' => false]);
        $this->assertDatabaseHas('social_providers', ['id' => $prov->id, 'enabled' => 0, 'auth_enabled' => 1, 'name' => 'New']);
    }

    public function testBooleanSettingIsClearedWhenMissingFromRequest()
    {
        $prov = SocialProvider::factory()->create(['supports_auth' => true, 'can_be_renamed' => true]);
        $c = new SocialProviderController();

        // create provider setting that is boolean and initially true
        $s1 = new ProviderSetting();
        $s1->provider()->associate($prov);
        $s1->name = 'AutoOpt';
        $s1->code = 'auto_opt';
        $s1->type = \App\Enums\SettingType::stBoolean;
        $s1->value = true;
        $s1->save();

        // build request that does NOT include 'auto_opt' so the elseif branch should run
        $req = \App\Http\Requests\Admin\SocialProviderUpdateRequest::create('/', 'POST', ['enabled' => 1, 'auth_enabled' => 0, 'name' => 'KeepName']);
        $resp = $c->update($req, $prov);

        $this->assertInstanceOf(RedirectResponse::class, $resp);
        $this->assertDatabaseHas('provider_settings', ['code' => 'auto_opt', 'value' => false]);
    }
}
