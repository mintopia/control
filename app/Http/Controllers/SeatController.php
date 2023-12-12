<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeatPickRequest;
use App\Models\Seat;
use App\Models\Ticket;
use Illuminate\Http\Request;

class SeatController extends Controller
{
    public function edit(Request $request, Seat $seat)
    {
        // Get any clan IDs that we are seating manager or leader for
        $tickets = $request->user()->getPickableTickets($seat->plan->event);
        if (!$tickets) {
            return response()->redirectToRoute('seatingplans.show', $seat->plan->code)->with('errorMessage', 'You have no seats available to pick');
        }

        if (count($tickets) === 1) {
            return $this->assignSeat($seat, $tickets[0]);
        }

        return view('seats.edit', [
            'seat' => $seat,
            'tickets' => $tickets,
        ]);
    }

    public function update(SeatPickRequest $request, Seat $seat)
    {
        return $this->assignSeat($seat, Ticket::find($request->ticket_id));
    }

    protected function assignSeat(Seat $seat, Ticket $ticket)
    {
        if ($seat->ticket) {
            $seat->ticket->seat_id = null;
            $seat->ticket->save();
        }
        $ticket->seat()->associate($seat);
        $ticket->save();
        return response()->redirectToRoute('seatingplans.show', $ticket->event->code)->with('successMessage', "You have chosen {$seat->label}");
    }
}
