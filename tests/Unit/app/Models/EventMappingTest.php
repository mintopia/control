<?php

namespace Tests\Unit\app\Models;

use App\Models\EventMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventMappingTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateAndRelations()
    {
        $m = new EventMapping();
        $this->assertInstanceOf(EventMapping::class, $m);
        $this->assertTrue(method_exists($m, 'provider'));
        $this->assertTrue(method_exists($m, 'event'));
    }
}
