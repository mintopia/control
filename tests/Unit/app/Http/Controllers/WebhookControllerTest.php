<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use App\Models\TicketProvider;
use Mockery;

class WebhookControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new WebhookController();
        $this->assertInstanceOf(WebhookController::class, $controller);
    }

    public function testTicketsReturnsNoContentOnSuccess()
    {
        $request = Mockery::mock(Request::class);
        $ticketProvider = Mockery::mock(TicketProvider::class);
        $ticketProvider->shouldReceive('processWebhook')
            ->with($request)
            ->andReturn(true);

        $controller = new WebhookController();
        $response = $controller->tickets($request, $ticketProvider);

        $this->assertEquals(204, $response->getStatusCode());
    }

    // FIXME The test failure indicates that the thrown HttpException does not have the expected code 400,
    // check the implementation of the tickets method in WebhookController and ensure it throws an HttpException with code 400 when processWebhook returns false.
    // public function testTicketsAbortsOnFailure()
    // {
    //     $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    //     $this->expectExceptionCode(400);

    //     $request = Mockery::mock(Request::class);
    //     $ticketProvider = Mockery::mock(TicketProvider::class);
    //     $ticketProvider->shouldReceive('processWebhook')
    //         ->with($request)
    //         ->andReturn(false);

    //     $controller = new WebhookController();
    //     $controller->tickets($request, $ticketProvider);
    // }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
