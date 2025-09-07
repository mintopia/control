<?php

namespace Tests\Unit\app\Services\TicketProviders\Traits;

use App\Models\TicketProvider;
use Carbon\Carbon;
use Database\Factories\EmailAddressFactory;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;
use Tests\Unit\app\Services\TicketProviders\HelperClasses\DummyProviderWithSyncAll;
use Tests\Unit\app\Services\TicketProviders\HelperClasses\InternalTicketStub;

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
        $ticketQuery = $this->partialMock(HasMany::class, function (MockInterface $mock) use ($internalTickets) {
            $mock->shouldReceive('whereIn', 'with')->andReturn($mock);
            $mock->shouldReceive('get')->andReturn(collect($internalTickets));
        });

        $typesQuery = $this->partialMock(HasMany::class, function (MockInterface $mock) use ($types) {
            $mock->shouldReceive('get')->andReturn(collect($types));
            $mock->shouldReceive('pluck')->andReturn(collect(array_keys($types)));
        });

        $provider = $this->partialMock(TicketProvider::class, function (MockInterface $mock) use ($ticketQuery, $typesQuery) {
            $mock->shouldReceive('types')->andReturn($typesQuery);
            $mock->shouldReceive('tickets')->andReturn($ticketQuery);
        });
        $provider->id = 42;
        $provider->code = 'dummy';
        return $provider;
    }

    public function testSyncAllTicketsRemovesVoidedTickets()
    {
        $remoteTicket = (object)[
            'id' => 1,
            'ticket_type_id' => 1,
            'status' => 'voided',
            'email' => 'test@example.com'
        ];
        $internalTicket = new InternalTicketStub(1);
        $provider = $this->getProvider([$remoteTicket], [$internalTicket]);
        $dummy = $this->partialMock(DummyProviderWithSyncAll::class, function (MockInterface $mock) use ($remoteTicket, $provider) {
            $mock->provider = $provider;
            $mock->shouldReceive('getTickets')->andReturn([$remoteTicket]);
        });

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

    public function testSyncAllTicketsAssociatesUserIfMissing()
    {
        $remoteTicket = (object)[
            'id' => 2,
            'ticket_type_id' => 1,
            'status' => 'valid',
            'email' => 'user@example.com'
        ];
        // Create a user and verified email address for lookup
        $email = EmailAddressFactory::new()->create([
            'email' => 'user@example.com',
            'verified_at' => Carbon::now(),
        ]);
        $user = $email->user;

        $internalTicket = new InternalTicketStub(2);
        $provider = $this->getProvider([$remoteTicket], [$internalTicket]);
        $dummy = $this->partialMock(DummyProviderWithSyncAll::class, function (MockInterface $mock) use ($remoteTicket, $provider) {
            $mock->provider = $provider;
            $mock->shouldReceive('getTickets')->andReturn([$remoteTicket]);
        });

        $buffer = new BufferedOutput();
        $output = new OutputStyle(new ArrayInput([]), $buffer);

        $dummy->syncAllTickets($output);

        $this->assertTrue($internalTicket->saved);
        $this->assertEquals($user->id, $internalTicket->user->id);
        $this->assertStringContainsString('Associating', $buffer->fetch());
    }

    public function testSyncAllTicketsCreatesNewTicketForMissing()
    {
        $remoteTicket = (object)[
            'id' => 3,
            'ticket_type_id' => 1,
            'status' => 'valid',
            'email' => 'new@example.com'
        ];
        // No internal tickets
        $provider = $this->getProvider([$remoteTicket], []);
        $dummy = $this->partialMock(DummyProviderWithSyncAll::class, function (MockInterface $mock) use ($remoteTicket, $provider) {
            $mock->provider = $provider;
            $mock->shouldReceive('getTickets')->andReturn([$remoteTicket]);
        });

        $buffer = new BufferedOutput();
        $output = new OutputStyle(new ArrayInput([]), $buffer);

        $dummy->syncAllTickets($output);
        $this->assertTrue($dummy->makeTicketCalled);
        $this->assertStringContainsString('Creating ticket for 3 - new@example.com', $buffer->fetch());
    }

    public function testSyncAllTicketsSkipsVoidedMissingTickets()
    {
        $remoteTicket = (object)[
            'id' => 4,
            'ticket_type_id' => 1,
            'status' => 'voided',
            'email' => 'voided@example.com'
        ];
        $provider = $this->getProvider([$remoteTicket], []);
        $dummy = $this->partialMock(DummyProviderWithSyncAll::class, function (MockInterface $mock) use ($remoteTicket, $provider) {
            $mock->provider = $provider;
            $mock->shouldReceive('getTickets')->andReturn([$remoteTicket]);
        });

        $buffer = new BufferedOutput();
        $output = new OutputStyle(new ArrayInput([]), $buffer);

        $dummy->syncAllTickets($output);
        $this->assertFalse($dummy->makeTicketCalled);
        $this->assertStringNotContainsString('Creating ticket for 4', $buffer->fetch());
    }
}
