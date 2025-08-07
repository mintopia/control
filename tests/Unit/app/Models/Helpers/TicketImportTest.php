<?php

namespace Tests\Unit\app\Models\Helpers;

use Tests\TestCase;
use App\Models\Helpers\TicketImport;

class TicketImportTest extends TestCase
{
    // FIX TicketImport is not available in the current namespace?
    // public function testCanInstantiateTicketImport()
    // {
    //     $import = new TicketImport();
    //     $this->assertInstanceOf(TicketImport::class, $import);
    // }

    public function testCanInstantiateWithAllArguments()
    {
        $user = $this->createMock(\App\Models\User::class);
        $event = $this->createMock(\App\Models\Event::class);
        $type = $this->createMock(\App\Models\TicketType::class);
        $seat = $this->createMock(\App\Models\Seat::class);
        $import = new TicketImport($user, $event, $type, $seat);
        $this->assertSame($user, $import->user);
        $this->assertSame($event, $import->event);
        $this->assertSame($type, $import->type);
        $this->assertSame($seat, $import->seat);
    }

    public function testCanInstantiateWithNullSeat()
    {
        $user = $this->createMock(\App\Models\User::class);
        $event = $this->createMock(\App\Models\Event::class);
        $type = $this->createMock(\App\Models\TicketType::class);
        $import = new TicketImport($user, $event, $type, null);
        $this->assertSame($user, $import->user);
        $this->assertSame($event, $import->event);
        $this->assertSame($type, $import->type);
        $this->assertNull($import->seat);
    }
}
