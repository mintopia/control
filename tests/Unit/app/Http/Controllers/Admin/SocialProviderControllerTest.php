<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\SocialProviderController;
use App\Models\SocialProvider;

class SocialProviderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testEditAndUpdate()
    {
        $prov = SocialProvider::factory()->create(['supports_auth' => true, 'can_be_renamed' => true]);
        $c = new SocialProviderController();
        $this->assertTrue(is_object($c->edit($prov)));
        $req = \App\Http\Requests\Admin\SocialProviderUpdateRequest::create('/', 'POST', ['enabled' => 1, 'auth_enabled' => 1, 'name' => 'New']);
        $resp = $c->update($req, $prov);
        $this->assertTrue(method_exists($resp, 'getTargetUrl'));
    }
}
