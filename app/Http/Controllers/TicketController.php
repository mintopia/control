<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketTransferRequest;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = $request->user()->tickets()->with(['event' => function ($query) {
            $query->orderBy('starts_at', 'DESC');
        }, 'type', 'seat'])->paginate();
        return view('tickets.index', [
            'tickets' => $tickets,
        ]);
    }

    public function show(Ticket $ticket)
    {
        return view('tickets.show', [
            'ticket' => $ticket,
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        if ($request->has('generate')) {
            $ticket->generateTransferCode();
            return response()->redirectToRoute('tickets.show', $ticket->id)->with('successMessage', 'A new transfer code has been generated');
        } elseif ($request->has('remove')) {
            $ticket->transfer_code = null;
            $ticket->save();
            return response()->redirectToRoute('tickets.show', $ticket->id)->with('successMessage', 'The transfer code has been removed');
        }
        return response()->redirectToRoute('tickets.show', $ticket->id);
    }

    public function transfer(TicketTransferRequest $request)
    {
        $ticket = Ticket::whereTransferCode($request->input('code'))->first();
        $ticket->user()->associate($request->user());
        $ticket->transfer_code = null;
        $ticket->save();
        return response()->redirectToRoute('tickets.show', $ticket->id)->with('successMessage', 'The ticket has been transferred to your account');
    }
}
