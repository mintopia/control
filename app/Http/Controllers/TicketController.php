<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = $request->user()->tickets()->paginate();
        return view('tickets.index', [
            'tickets' => $tickets,
        ]);
    }
}
