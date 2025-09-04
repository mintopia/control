<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\TicketTypeMappingController;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\TicketTypeMapping;

class TicketTypeMappingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateEditDeleteViews()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = \App\Models\TicketProvider::factory()->create();
        $mapping = new \App\Models\TicketTypeMapping();
        $mapping->type()->associate($type);
        $mapping->provider()->associate($provider);
        $mapping->external_id = 'x1';
        $mapping->save();
        $c = new TicketTypeMappingController();
        $this->assertTrue(is_object($c->create($event, $type)));
        $this->assertTrue(is_object($c->edit($event, $type, $mapping)));
        $this->assertTrue(is_object($c->delete($event, $type, $mapping)));
    }
}
