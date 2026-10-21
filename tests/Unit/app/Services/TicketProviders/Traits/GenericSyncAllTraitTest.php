<?php

namespace Tests\Unit\app\Services\TicketProviders\Traits;

use App\Models\EmailAddress;
use App\Models\Ticket;
use App\Models\TicketProvider;
use App\Models\TicketType;
use App\Models\TicketTypeMapping;
use Carbon\Carbon;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class GenericSyncAllTraitTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Log::spy();
    }

    protected function makeProviderWithRelations(array $internalTickets = [], array $typeExternalIds = [1, 2])
    {
        // Create a real TicketProvider backed by DB so relations work naturally
        $provider = TicketProvider::factory()->create([
            'name' => 'Dummy',
            'code' => 'dummy',
            'provider_class' => \App\Services\TicketProviders\TicketTailorProvider::class,
        ]);

        // Create TicketTypes and mappings for the provider
        foreach ($typeExternalIds as $ext) {
            $type = TicketType::factory()->create();
            TicketTypeMapping::create([
                'ticket_type_id' => $type->id,
                'ticket_provider_id' => $provider->id,
                'external_id' => (string)$ext,
            ]);
        }

        // Create internal tickets if provided
        foreach ($internalTickets as $t) {
            Ticket::factory()->create([
                'ticket_provider_id' => $provider->id,
                'external_id' => $t['external_id'],
                'user_id' => $t['user_id'] ?? null,
            ]);
        }

        return $provider;
    }

    protected function makeDummyUsingTrait($provider, array $remoteTickets = [])
    {
        // Build an anonymous class that includes the trait and mimics a provider
        $anon = new class ($provider) {
            public $provider;
            public $makeTicketCalled = false;
            private ?array $remoteTickets = null;

            public function __construct($p)
            {
                $this->provider = $p;
            }

            use \App\Services\TicketProviders\Traits\GenericSyncAllTrait;

            // allow tests to override remote tickets easily
            protected function getTickets(): array
            {
                return $this->remoteTickets ?? [];
            }

            public function setRemoteTickets(array $tickets): void
            {
                $this->remoteTickets = $tickets;
            }

            protected function makeTicket($user, $data)
            {
                $this->makeTicketCalled = true;
                // Create event and type so foreign keys satisfy DB constraints
                $event = \App\Models\Event::factory()->create();
                $type = \App\Models\TicketType::factory()->for($event)->create();

                $ticket = Ticket::factory()->create([
                    'ticket_provider_id' => $this->provider->id,
                    'external_id' => $data->id,
                    'original_email' => $data->email ?? null,
                    'event_id' => $event->id,
                    'ticket_type_id' => $type->id,
                ]);
                return $ticket;
            }
        };

        $anon->setRemoteTickets($remoteTickets);
        return $anon;
    }

    public function testSyncAllTicketsRemovesVoidedTickets()
    {
        $remoteTicket = (object)[
            'id' => 1,
            'ticket_type_id' => 1,
            'status' => 'voided',
            'email' => 'test@example.com'
        ];

        // Create an internal ticket that should be deleted
        $provider = $this->makeProviderWithRelations([
            ['external_id' => 1]
        ]);

        $anon = $this->makeDummyUsingTrait($provider, [$remoteTicket]);

        $buffer = new BufferedOutput();
        $output = new OutputStyle(new ArrayInput([]), $buffer);

        // ensure precondition
        $this->assertDatabaseHas('tickets', ['external_id' => 1]);

        $anon->syncAllTickets($output);

        $this->assertDatabaseMissing('tickets', ['external_id' => 1]);
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

        // Create verified email and related user
        $email = EmailAddress::factory()->create([
            'email' => 'user@example.com',
            'verified_at' => Carbon::now(),
        ]);

        // internal ticket without user
        $provider = $this->makeProviderWithRelations([
            ['external_id' => 2]
        ]);

        $anon = $this->makeDummyUsingTrait($provider, [$remoteTicket]);

        $buffer = new BufferedOutput();
        $output = new OutputStyle(new ArrayInput([]), $buffer);

        $anon->syncAllTickets($output);

        // ticket should now be associated with user
        $this->assertDatabaseHas('tickets', ['external_id' => 2]);
        $ticket = Ticket::whereExternalId(2)->first();
        $this->assertNotNull($ticket->user_id);
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

        $provider = $this->makeProviderWithRelations([]);
        $anon = $this->makeDummyUsingTrait($provider, [$remoteTicket]);

        $buffer = new BufferedOutput();
        $output = new OutputStyle(new ArrayInput([]), $buffer);

        $anon->syncAllTickets($output);

        $this->assertTrue($anon->makeTicketCalled);
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

        $provider = $this->makeProviderWithRelations([]);
        $anon = $this->makeDummyUsingTrait($provider, [$remoteTicket]);

        $buffer = new BufferedOutput();
        $output = new OutputStyle(new ArrayInput([]), $buffer);

        $anon->syncAllTickets($output);

        $this->assertFalse($anon->makeTicketCalled);
        $this->assertStringNotContainsString('Creating ticket for 4', $buffer->fetch());
    }
}
