<?php

namespace Tests\Unit\app\Models\Helpers;

use Tests\TestCase;
use App\Models\Helpers\TicketImport;

class TicketImportTest extends TestCase
{
    public function testCanInstantiateTicketImport()
    {
        $import = new TicketImport();
        $this->assertInstanceOf(TicketImport::class, $import);
    }
}
