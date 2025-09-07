<?php

namespace Tests\Unit\app\Models\HelperClasses;

use App\Models\ClanRole;

class DummyClanRole extends ClanRole
{
    public function toStringNamePublic(): string
    {
        return $this->toStringName();
    }
}
