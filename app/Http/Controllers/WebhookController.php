<?php

namespace App\Http\Controllers;

use App\Models\TicketProvider;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function tickets(Request $request, TicketProvider $ticketprovider)
    {
        if ($ticketprovider->processWebhook($request)) {
            return response()->noContent();
        } else {
            abort(400);
        }
    }
}
