<?php

namespace Tests\Unit\app\Models\HelperClasses;

use App\Models\Seat;

class DummySeat extends Seat
{
    public function toStringNamePublic(): string
    {
        return $this->toStringName();
    }
}
