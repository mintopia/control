<?php

namespace Tests\Unit\app\Models\HelperClasses;

use App\Models\Clan;

class DummyClan extends Clan
{
    public function exposeToString(): string
    {
        return $this->toStringName();
    }
}
