<?php

namespace Tests\Unit\app\Services\TicketProviders;

use App\Models\TicketProvider;
use App\Services\TicketProviders\FakeProvider;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class FakeProviderTest extends TestCase
{
    use RefreshDatabase;

    public function testConfigMappingReturnsArray()
    {
        $provider = new FakeProvider();
        $mapping = $provider->configMapping();

        $this->assertIsArray($mapping);
        $this->assertEmpty($mapping);
    }

    public function testInstallReturnsGivenProvider()
    {
        $tp = TicketProvider::factory()->create();
        $provider = new FakeProvider($tp);

        $result = $provider->install();
        $this->assertInstanceOf(TicketProvider::class, $result);
        $this->assertEquals($tp->id, $result->id);
    }

    public function testInstallThrowsWhenNoProvider()
    {
        $this->expectException(RuntimeException::class);

        $provider = new FakeProvider();
        $provider->install();
    }

    public function testProcessWebhookReturnsTrue()
    {
        $provider = new FakeProvider();
        $request = Request::create('/webhook', 'POST');

        $this->assertTrue($provider->processWebhook($request));
    }

    public function testSyncTicketsIsNoopAndReturnsNull()
    {
        $provider = new FakeProvider();
        $this->assertNull($provider->syncTickets('user@example.com'));
    }

    public function testGetEventsAndTicketTypesReturnEmptyArrays()
    {
        $provider = new FakeProvider();

        $this->assertEquals([], $provider->getEvents());
        $this->assertEquals([], $provider->getTicketTypes('event-id'));
    }

    public function testSyncAllTicketsWritesToOutputWhenOutputstyleProvided()
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
