<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\SeatObserver;
use App\Models\Seat;

class SeatObserverTest extends TestCase
{
    public function testSavingSetsLabelIfMissing()
    {
        $seat = new Seat(['row' => 'A', 'number' => 1, 'label' => null]);
        $observer = new SeatObserver();
        $observer->saving($seat);
        $this->assertNotNull($seat->label);
    }
}
