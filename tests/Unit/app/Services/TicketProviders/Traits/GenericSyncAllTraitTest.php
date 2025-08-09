<?php

namespace Tests\Unit\app\Services\TicketProviders\Traits;

use Tests\TestCase;
use App\Services\TicketProviders\Traits\GenericSyncAllTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Mockery;

class DummyProviderWithSyncAll
{
    use GenericSyncAllTrait;

    public $provider;
    public $makeTicketCalled = false;
    public $makeTicketArgs = [];

    public function __construct($provider)
    {
        $this->provider = $provider;
    }

    public function getTickets()
    {
        return $this->provider->remoteTickets;
    }

    // FIXME  This function creates a ticket but ID does not exist...? it isn't used either...?
    // public function makeTicket($a, $b)
    // {
    //     $this->makeTicketCalled = true;
    //     $this->makeTicketArgs[] = [$a, $b];
    //     // Return a dummy ticket object
    //     return (object)['id' => $b->id, '__toString' => function () {
    //         return 'Ticket#' . $this->id;
    //     }];
    // }
}

class GenericSyncAllTraitTest extends TestCase
{
    // CHECK Seeding issue?
    // public function setUp(): void
    // {
    //     parent::setUp();
    //     Log::spy();
    // }

    // protected function getProvider($remoteTickets = [], $internalTickets = [], $types = [1, 2])
    // {
    //     $provider = new class {
    //         public $remoteTickets = [];
    //         public function types()
    //         {
    //             return new class($this) {
    //                 public $parent;
    //                 public function __construct($parent)
    //                 {
    //                     $this->parent = $parent;
    //                 }
    //                 public function pluck($col)
    //                 {
    //                     return collect([1, 2]);
    //                 }
    //             };
    //         }
    //         public function tickets()
    //         {
    //             return new class($this) {
    //                 public $parent;
    //                 public $internalTickets = [];
    //                 public function __construct($parent)
    //                 {
    //                     $this->parent = $parent;
    //                 }
    //                 public function whereIn($col, $ids)
    //                 {
    //                     return $this;
    //                 }
    //                 public function with($rel)
    //                 {
    //                     return $this;
    //                 }
    //                 public function get()
    //                 {
    //                     return collect($this->internalTickets);
    //                 }
    //             };
    //         }
    //     };
    //     $provider->remoteTickets = $remoteTickets;
    //     $provider->tickets()->internalTickets = $internalTickets;
    //     return $provider;
    // }

    // public function test_sync_all_tickets_removes_voided_tickets()
    // {
    //     $remoteTicket = (object)[
    //         'id' => 1,
    //         'ticket_type_id' => 1,
    //         'status' => 'voided',
    //         'email' => 'test@example.com'
    //     ];
    //     $internalTicket = Mockery::mock();
    //     $internalTicket->external_id = 1;
    //     $internalTicket->user = null;
    //     $internalTicket->__toString = function () {
    //         return 'Ticket#1';
    //     };
    //     $internalTicket->shouldReceive('delete')->once();

    //     $provider = $this->getProvider([$remoteTicket], [$internalTicket]);
    //     $dummy = new DummyProviderWithSyncAll($provider);

    //     $output = Mockery::mock(OutputStyle::class);
    //     $output->shouldReceive('writeln')->withArgs(function ($msg) {
    //         return str_contains($msg, 'has been voided, removing');
    //     })->once();

    //     $dummy->syncAllTickets($output);
    // }

    // public function test_sync_all_tickets_associates_user_if_missing()
    // {
    //     $remoteTicket = (object)[
    //         'id' => 2,
    //         'ticket_type_id' => 1,
    //         'status' => 'valid',
    //         'email' => 'user@example.com'
    //     ];
    //     $user = (object)['id' => 5, '__toString' => function () {
    //         return 'User#5';
    //     }];
    //     $emailModel = Mockery::mock();
    //     $emailModel->user = $user;

    //     $internalTicket = Mockery::mock();
    //     $internalTicket->external_id = 2;
    //     $internalTicket->user = null;
    //     $internalTicket->__toString = function () {
    //         return 'Ticket#2';
    //     };
    //     $internalTicket->shouldReceive('user')->andReturnSelf();
    //     $internalTicket->shouldReceive('associate')->with($user)->once();
    //     $internalTicket->shouldReceive('save')->once();

    //     // Mock EmailAddress::whereEmail()->where()->with()->first()
    //     $emailQuery = Mockery::mock();
    //     $emailQuery->shouldReceive('where')->andReturnSelf();
    //     $emailQuery->shouldReceive('with')->andReturnSelf();
    //     $emailQuery->shouldReceive('first')->andReturn($emailModel);

    //     $emailStatic = Mockery::mock('alias:App\Models\EmailAddress');
    //     $emailStatic->shouldReceive('whereEmail')->with('user@example.com')->andReturn($emailQuery);

    //     $provider = $this->getProvider([$remoteTicket], [$internalTicket]);
    //     $dummy = new DummyProviderWithSyncAll($provider);

    //     $output = Mockery::mock(OutputStyle::class);
    //     $output->shouldReceive('writeln')->withArgs(function ($msg) {
    //         return str_contains($msg, 'Associating');
    //     })->once();

    //     $dummy->syncAllTickets($output);
    // }

    // public function test_sync_all_tickets_creates_new_ticket_for_missing()
    // {
    //     $remoteTicket = (object)[
    //         'id' => 3,
    //         'ticket_type_id' => 1,
    //         'status' => 'valid',
    //         'email' => 'new@example.com'
    //     ];
    //     // No internal tickets
    //     $provider = $this->getProvider([$remoteTicket], []);
    //     $dummy = new DummyProviderWithSyncAll($provider);

    //     $output = Mockery::mock(OutputStyle::class);
    //     $output->shouldReceive('writeln')->withArgs(function ($msg) {
    //         return str_contains($msg, 'Creating ticket for 3 - new@example.com');
    //     })->once();
    //     $output->shouldReceive('writeln')->withArgs(function ($msg) {
    //         return str_contains($msg, 'Created');
    //     })->once();

    //     $dummy->syncAllTickets($output);
    //     $this->assertTrue($dummy->makeTicketCalled);
    // }

    // public function test_sync_all_tickets_skips_voided_missing_tickets()
    // {
    //     $remoteTicket = (object)[
    //         'id' => 4,
    //         'ticket_type_id' => 1,
    //         'status' => 'voided',
    //         'email' => 'voided@example.com'
    //     ];
    //     $provider = $this->getProvider([$remoteTicket], []);
    //     $dummy = new DummyProviderWithSyncAll($provider);

    //     $output = Mockery::mock(OutputStyle::class);
    //     $output->shouldNotReceive('writeln')->withArgs(function ($msg) {
    //         return str_contains($msg, 'Creating ticket for 4');
    //     });

    //     $dummy->syncAllTickets($output);
    //     $this->assertFalse($dummy->makeTicketCalled);
    // }
}
