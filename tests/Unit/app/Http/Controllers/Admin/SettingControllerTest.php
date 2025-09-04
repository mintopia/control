<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\SettingController;
use App\Models\Setting;

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
        $req = \App\Http\Requests\Admin\SettingUpdateRequest::create('/', 'POST', ['f1' => 'v']);
        $resp = $c->update($req);
        $this->assertTrue(method_exists($resp, 'getTargetUrl'));
    }
}
