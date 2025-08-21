<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\EventObserver;
use App\Models\Event;

class EventObserverTest extends TestCase
{
    public function testSavingSetsCodeIfMissing()
    {
        $event = new Event(['name' => 'Test Event', 'code' => null]);
        $observer = new EventObserver();
        $observer->saving($event);
        $this->assertNotEmpty($event->code);
    }
}
