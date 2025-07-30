<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\WebhookController;

class WebhookControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new WebhookController();
        $this->assertInstanceOf(WebhookController::class, $controller);
    }
}
