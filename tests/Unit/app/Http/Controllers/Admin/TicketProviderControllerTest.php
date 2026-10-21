<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Enums\SettingType;
use App\Http\Controllers\Admin\TicketProviderController;
use App\Http\Requests\Admin\TicketProviderUpdateRequest;
use App\Models\ProviderSetting;
use App\Models\TicketProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Tests\TestCase;

class TicketProviderControllerTest extends TestCase
{
    use RefreshDatabase;

    // Separate tests for each controller action
    public function testEditReturnsView()
    {
        $prov = TicketProvider::factory()->create();
        $c = new TicketProviderController();
        $this->assertTrue(is_object($c->edit($prov)));
    }

    public function testClearcacheReturnsRedirect()
    {
        $prov = TicketProvider::factory()->create();
        $c = new TicketProviderController();
        $resp = $c->clearcache($prov);
        $this->assertInstanceOf(RedirectResponse::class, $resp);
    }

    public function testSyncReturnsRedirect()
    {
        $prov = TicketProvider::factory()->create();
        $c = new TicketProviderController();
        $resp = $c->sync($prov);
        $this->assertInstanceOf(RedirectResponse::class, $resp);
    }

    public function testUpdateReturnsRedirect()
    {
        $prov = TicketProvider::factory()->create(['name' => 'P1']);
        $controller = new TicketProviderController();
        $req = TicketProviderUpdateRequest::create('/', 'POST', ['enabled' => 0]);
        $resp = $controller->update($req, $prov);
        $this->assertInstanceOf(RedirectResponse::class, $resp);
    }

    public function testUpdateSavesProviderSetting()
    {
        $prov = TicketProvider::factory()->create(['name' => 'P1']);
        // create provider settings
        $s1 = new ProviderSetting();
        $s1->provider()->associate($prov);
        $s1->name = 'Option';
        $s1->code = 'opt1';
        $s1->type = SettingType::stBoolean;
        $s1->value = true;
        $s1->save();

        $controller = new TicketProviderController();
        $req = TicketProviderUpdateRequest::create('/', 'POST', ['opt1' => 0]);
        $controller->update($req, $prov);
        $this->assertDatabaseHas('provider_settings', ['code' => 'opt1', 'value' => false]);
    }

    public function testUpdateSavesEnabledFlag()
    {
        $prov = TicketProvider::factory()->create(['name' => 'P1', 'enabled' => 1]);
        $controller = new TicketProviderController();
        $req = TicketProviderUpdateRequest::create('/', 'POST', ['enabled' => 0]);
        $controller->update($req, $prov);
        $this->assertDatabaseHas('ticket_providers', ['id' => $prov->id, 'enabled' => 0]);
    }

    public function testBooleanSettingIsClearedWhenMissingFromRequest()
    {
        $prov = TicketProvider::factory()->create(['name' => 'P2']);

        // create boolean provider setting that is true
        $s1 = new ProviderSetting();
        $s1->provider()->associate($prov);
        $s1->name = 'AutoSync';
        $s1->code = 'auto_sync';
        $s1->type = SettingType::stBoolean;
        $s1->value = true;
        $s1->save();

        $controller = new TicketProviderController();
        // request does not include 'auto_sync' so elseif branch should set it false
        $req = TicketProviderUpdateRequest::create('/', 'POST', ['enabled' => 1]);
        $controller->update($req, $prov);
        $this->assertDatabaseHas('provider_settings', ['code' => 'auto_sync', 'value' => false]);
    }
}
