<?php

namespace Tests\Unit\app\Http\Controllers;

use App\Http\Controllers\WebhookController;
use App\Models\TicketProvider;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new WebhookController();
        $this->assertInstanceOf(WebhookController::class, $controller);
    }

    public function testTicketsReturnsNoContentOnSuccess()
    {
        $request = Request::create('/webhook', 'POST');

        // Create a lightweight provider object with a processWebhook method
        $ticketProvider = new class extends TicketProvider {
            public function processWebhook(Request $request): bool
            {
                return true;
            }
        };

        $controller = new WebhookController();
        $response = $controller->tickets($request, $ticketProvider);

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testTicketsAbortsOnFailure()
    {
        $request = Request::create('/webhook', 'POST');

        $ticketProvider = new class extends TicketProvider {
            public function processWebhook(Request $request): bool
            {
                return false;
            }
        };

        $controller = new WebhookController();

        try {
            $controller->tickets($request, $ticketProvider);
            $this->fail('Expected HttpException to be thrown');
        } catch (HttpException $e) {
            $this->assertEquals(400, $e->getStatusCode());
        }
    }
}
