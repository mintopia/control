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
        if (!$seat->canPick($request->user())) {
            return response()->redirectToRoute('seatingplans.show', $seat->plan->event->code)->with('errorMessage', "That seat is not available")->withFragment("tab-plan-{$seat->plan->code}");
        }
        // Get any clan IDs that we are seating manager or leader for
        $tickets = $request->user()->getPickableTickets($seat->plan->event);
        if (count($tickets) === 0) {
            return response()->redirectToRoute('seatingplans.show', $seat->plan->event->code)->with('errorMessage', 'You have no seats available to pick')->withFragment("tab-plan-{$seat->plan->code}");
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
        if (!$seat->canPick($request->user())) {
            return response()->redirectToRoute('seatingplans.show', $seat->plan->event->code)->with('errorMessage', "That seat is not available")->withFragment("tab-plan-{$seat->plan->code}");
        }
        $ticket = Ticket::find($request->ticket_id);
        if ($seat->ticket && $ticket->seat && $request->input('swap', false)) {
            $oldSeat = $ticket->seat;
            if ($ticket->id !== $seat->ticket->id) {
                $oldSeat->ticket()->associate($seat->ticket);
                $oldSeat->saveQuietly();
            }
        }
        return $this->assignSeat($seat, $ticket);
    }

    protected function assignSeat(Seat $seat, Ticket $ticket)
    {
        $seat->ticket()->associate($ticket);
        $seat->save();
        return response()->redirectToRoute('seatingplans.show', $ticket->event->code)->with('successMessage', "You have chosen {$seat->label}")->withFragment("tab-plan-{$seat->plan->code}");
    }
}
