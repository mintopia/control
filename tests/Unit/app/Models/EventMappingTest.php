<?php

namespace Tests\Unit\app\Models;

use App\Models\EventMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_instantiate_and_relations()
    {
        $m = new EventMapping();
        $this->assertInstanceOf(EventMapping::class, $m);
        $this->assertTrue(method_exists($m, 'provider'));
        $this->assertTrue(method_exists($m, 'event'));
    }
}
