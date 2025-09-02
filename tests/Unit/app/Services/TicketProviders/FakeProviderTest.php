<?php

namespace Tests\Unit\app\Services\TicketProviders;

use App\Models\TicketProvider;
use App\Services\TicketProviders\FakeProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use RuntimeException;
use Tests\TestCase;

class FakeProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_mapping_returns_array()
    {
        $provider = new FakeProvider();
        $mapping = $provider->configMapping();

        $this->assertIsArray($mapping);
        $this->assertEmpty($mapping);
    }

    public function test_install_returns_given_provider()
    {
        $tp = TicketProvider::factory()->create();
        $provider = new FakeProvider($tp);

        $result = $provider->install();
        $this->assertInstanceOf(TicketProvider::class, $result);
        $this->assertEquals($tp->id, $result->id);
    }

    public function test_install_throws_when_no_provider()
    {
        $this->expectException(RuntimeException::class);

        $provider = new FakeProvider();
        $provider->install();
    }

    public function test_process_webhook_returns_true()
    {
        $provider = new FakeProvider();
        $request = Request::create('/webhook', 'POST');

        $this->assertTrue($provider->processWebhook($request));
    }

    public function test_sync_tickets_is_noop_and_returns_null()
    {
        $provider = new FakeProvider();
        $this->assertNull($provider->syncTickets('user@example.com'));
    }

    public function test_get_events_and_ticket_types_return_empty_arrays()
    {
        $provider = new FakeProvider();

        $this->assertEquals([], $provider->getEvents());
        $this->assertEquals([], $provider->getTicketTypes('event-id'));
    }

    public function test_sync_all_tickets_writes_to_output_when_outputstyle_provided()
    {
        $provider = new FakeProvider();

        $input = new StringInput('');
        $buffer = new BufferedOutput();
        // Illuminate\Console\OutputStyle wraps SymfonyStyle and accepts Input/Output in constructor
        $style = new OutputStyle($input, $buffer);

        $provider->syncAllTickets($style);

        $content = $buffer->fetch();
        $this->assertStringContainsString('FakeProvider: syncAllTickets called', $content);
    }
}
