<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\TicketProviderController;
use App\Models\TicketProvider;
use App\Models\ProviderSetting;
use Illuminate\Http\RedirectResponse;

use App\Enums\SettingType;

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
        $req = \App\Http\Requests\Admin\TicketProviderUpdateRequest::create('/', 'POST', ['enabled' => 0]);
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
        $req = \App\Http\Requests\Admin\TicketProviderUpdateRequest::create('/', 'POST', ['opt1' => 0]);
        $controller->update($req, $prov);
        $this->assertDatabaseHas('provider_settings', ['code' => 'opt1', 'value' => false]);
    }

    public function testUpdateSavesEnabledFlag()
    {
        $prov = TicketProvider::factory()->create(['name' => 'P1', 'enabled' => 1]);
        $controller = new TicketProviderController();
        $req = \App\Http\Requests\Admin\TicketProviderUpdateRequest::create('/', 'POST', ['enabled' => 0]);
        $controller->update($req, $prov);
        $this->assertDatabaseHas('ticket_providers', ['id' => $prov->id, 'enabled' => 0]);
    }
}
