<?php

namespace Tests\Unit\app\Models\HelperClasses;

use App\Models\Role;

class DummyRole extends Role
{
    public function toStringNamePublic(): string
    {
        return $this->toStringName();
    }
}
