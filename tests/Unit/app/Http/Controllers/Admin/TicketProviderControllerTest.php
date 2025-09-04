<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\TicketProviderController;
use App\Models\TicketProvider;

class TicketProviderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testEditClearcacheSync()
    {
        $prov = TicketProvider::factory()->create();
        $c = new TicketProviderController();
        $this->assertTrue(is_object($c->edit($prov)));
        $this->assertTrue(method_exists($c->clearcache($prov), 'getTargetUrl'));
        $this->assertTrue(method_exists($c->sync($prov), 'getTargetUrl'));
    }
}
