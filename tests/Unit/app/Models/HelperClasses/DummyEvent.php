<?php

namespace Tests\Unit\app\Models\HelperClasses;

use App\Models\Event;

class DummyEvent extends Event
{
    public function toStringNamePublic(): string
    {
        return $this->toStringName();
    }
}
