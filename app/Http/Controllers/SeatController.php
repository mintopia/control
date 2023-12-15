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
        if (count($tickets) === 0) {
            return response()->redirectToRoute('seatingplans.show', $seat->plan->event->code)->with('errorMessage', 'You have no seats available to pick');
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
        $seat->ticket()->associate($ticket);
        $seat->save();
        return response()->redirectToRoute('seatingplans.show', $ticket->event->code)->with('successMessage', "You have chosen {$seat->label}");
    }
}
