<?php

namespace Tests\Unit\app\Services\TicketProviders\Traits;

use Tests\TestCase;
use App\Services\TicketProviders\Traits\GenericSyncAllTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class DummyProviderWithSyncAll
{
    use GenericSyncAllTrait;

    public $provider;
    public $makeTicketCalled = false;
    public $makeTicketArgs = [];

    public function __construct($provider)
    {
        // use the passed provider object but ensure it's stringable for logging
        $providerObj = $provider;
        if (!method_exists($providerObj, '__toString')) {
            $providerObj = new class($provider) {
                private $inner;
                public function __construct($inner)
                {
                    $this->inner = $inner;
                }
                public function __toString()
                {
                    return 'DummyProvider';
                }
                // forward dynamic property access to inner if needed
                public function __get($k)
                {
                    return $this->inner->$k ?? null;
                }
                // forward method calls to inner provider
                public function __call($name, $args)
                {
                    return call_user_func_array([$this->inner, $name], $args);
                }
            };
        }
        $this->provider = $providerObj;
    }

    public function getTickets()
    {
        return $this->provider->remoteTickets;
    }

    public function makeTicket($a, $b)
    {
        $this->makeTicketCalled = true;
        $this->makeTicketArgs[] = [$a, $b];
        // Return a dummy ticket object
        return new class($b->id) {
            public $id;
            public function __construct($id)
            {
                $this->id = $id;
            }
            public function __toString()
            {
                return 'Ticket#' . $this->id;
            }
        };
    }
}

class InternalTicketStub
{
    public $external_id;
    public $user = null;
    public $deleted = false;
    public $saved = false;

    public function __construct($external_id)
    {
        $this->external_id = $external_id;
    }

    public function __toString()
    {
        return 'Ticket#' . $this->external_id;
    }

    public function delete()
    {
        $this->deleted = true;
    }

    public function user()
    {
        $parent = $this;
        return new class($parent) {
            private $parent;
            public function __construct($parent)
            {
                $this->parent = $parent;
            }
            public function associate($user)
            {
                $this->parent->user = $user;
            }
        };
    }

    public function save()
    {
        $this->saved = true;
    }
}

// We'll use a BufferedOutput and real OutputStyle in tests to capture output

class GenericSyncAllTraitTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Log::spy();
    }

    protected function getProvider($remoteTickets = [], $internalTickets = [], $types = [1, 2])
    {
        // create a persistent ticket query object so tests can set internalTickets on it
        $ticketQuery = new class {
            public $internalTickets = [];
            public function whereIn($col, $ids)
            {
                return $this;
            }
            public function with($rel)
            {
                return $this;
            }
            public function get()
            {
                return collect($this->internalTickets);
            }
        };

        $provider = new class($ticketQuery) {
            public $remoteTickets = [];
            private $ticketQuery;
            public function __construct($ticketQuery)
            {
                $this->ticketQuery = $ticketQuery;
            }
            public function types()
            {
                return new class($this) {
                    public $parent;
                    public function __construct($parent)
                    {
                        $this->parent = $parent;
                    }
                    public function pluck($col)
                    {
                        return collect([1, 2]);
                    }
                };
            }
            public function tickets()
            {
                return $this->ticketQuery;
            }
        };

        $provider->remoteTickets = $remoteTickets;
        $provider->tickets()->internalTickets = $internalTickets;
        return $provider;
    }

    public function test_sync_all_tickets_removes_voided_tickets()
    {
        $remoteTicket = (object)[
            'id' => 1,
            'ticket_type_id' => 1,
            'status' => 'voided',
            'email' => 'test@example.com'
        ];
        $internalTicket = new InternalTicketStub(1);
        $provider = $this->getProvider([$remoteTicket], [$internalTicket]);
        $dummy = new DummyProviderWithSyncAll($provider);

        $buffer = new BufferedOutput();
        $output = new OutputStyle(new ArrayInput([]), $buffer);

        // preconditions: ensure provider and dummy return the tickets we expect
        $this->assertCount(1, $provider->tickets()->get(), 'provider should return one internal ticket');
        $this->assertEquals(1, $provider->tickets()->get()->first()->external_id, 'internal ticket external_id mismatch');
        $this->assertCount(1, $dummy->getTickets(), 'dummy provider should return one remote ticket');
        $this->assertEquals(1, $dummy->getTickets()[0]->id, 'remote ticket id mismatch');

        $dummy->syncAllTickets($output);

        $this->assertTrue($internalTicket->deleted);
        $this->assertStringContainsString('has been voided, removing', $buffer->fetch());
    }

    public function test_sync_all_tickets_associates_user_if_missing()
    {
        $remoteTicket = (object)[
            'id' => 2,
            'ticket_type_id' => 1,
            'status' => 'valid',
            'email' => 'user@example.com'
        ];
        // Create a user and verified email address for lookup
        $email = \Database\Factories\EmailAddressFactory::new()->create([
            'email' => 'user@example.com',
            'verified_at' => Carbon::now(),
        ]);
        $user = $email->user;

        $internalTicket = new InternalTicketStub(2);
        $provider = $this->getProvider([$remoteTicket], [$internalTicket]);
        $dummy = new DummyProviderWithSyncAll($provider);

        $buffer = new BufferedOutput();
        $output = new OutputStyle(new ArrayInput([]), $buffer);

        $dummy->syncAllTickets($output);

        $this->assertTrue($internalTicket->saved);
        $this->assertEquals($user->id, $internalTicket->user->id);
        $this->assertStringContainsString('Associating', $buffer->fetch());
    }

    public function test_sync_all_tickets_creates_new_ticket_for_missing()
    {
        $remoteTicket = (object)[
            'id' => 3,
            'ticket_type_id' => 1,
            'status' => 'valid',
            'email' => 'new@example.com'
        ];
        // No internal tickets
        $provider = $this->getProvider([$remoteTicket], []);
        $dummy = new DummyProviderWithSyncAll($provider);

        $buffer = new BufferedOutput();
        $output = new OutputStyle(new ArrayInput([]), $buffer);

        $dummy->syncAllTickets($output);
        $this->assertTrue($dummy->makeTicketCalled);
        $this->assertStringContainsString('Creating ticket for 3 - new@example.com', $buffer->fetch());
    }

    public function test_sync_all_tickets_skips_voided_missing_tickets()
    {
        $remoteTicket = (object)[
            'id' => 4,
            'ticket_type_id' => 1,
            'status' => 'voided',
            'email' => 'voided@example.com'
        ];
        $provider = $this->getProvider([$remoteTicket], []);
        $dummy = new DummyProviderWithSyncAll($provider);

        $buffer = new BufferedOutput();
        $output = new OutputStyle(new ArrayInput([]), $buffer);

        $dummy->syncAllTickets($output);
        $this->assertFalse($dummy->makeTicketCalled);
        $this->assertStringNotContainsString('Creating ticket for 4', $buffer->fetch());
    }
}
