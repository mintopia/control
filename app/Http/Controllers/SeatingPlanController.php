<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Seat;
use Illuminate\Http\Request;

class SeatingPlanController extends Controller
{
    public function index()
    {
        $events = Event::orderBy('starts_at', 'DESC')->with('seatingPlans')->paginate();
        return view('seatingplans.index', [
            'events' => $events,
        ]);
    }

    public function show(Request $request, Event $event)
    {
        if ($event->seating_locked) {
            $request->session()->now('infoMessage', 'Seating is locked');
        }
        // Get clans and tickets (left-hand sidebar)
        $clans = $request->user()->clanMemberships()->with('clan')->get()->pluck('clan');
        $clanIds = $clans->pluck('id');
        $allTickets = $event->tickets()
            ->whereUserId($request->user()->id)->orWhere(function ($query) use ($clanIds) {
                $query->whereHas('user', function ($query) use ($clanIds) {
                    $query->whereHas('clanmemberships', function ($query) use ($clanIds) {
                        $query->whereIn('clan_id', $clanIds);
                    });
                });
            })
            ->with(['seat', 'event', 'user' => function ($query) {
                $query->orderBy('nickname', 'ASC');
            }, 'user.clanMemberships'])
            ->get();

        $tickets = [
            0 => [],
        ];
        $clanSeats = [];
        $mySeats = [];
        foreach ($allTickets as $ticket) {
            if ($ticket->user->id === $request->user()->id) {
                $tickets[0][] = $ticket;
                if ($ticket->seat_id) {
                    $mySeats[] = $ticket->seat_id;
                }
                continue;
            }
            foreach ($ticket->user->clanMemberships as $clanMember) {
                if (!isset($tickets[$clanMember->clan_id])) {
                    $tickets[$clanMember->clan_id] = [];
                }
                $tickets[$clanMember->clan_id][] = $ticket;
                if ($ticket->seat_id) {
                    $clanSeats[] = $ticket->seat_id;
                }
            }
        }

        // Now our seat data
        $seats = [];
        foreach ($event->seatingPlans as $plan) {
            $seats[$plan->id] = $plan->getData();
        }

        return view('seatingplans.show', [
            'clans' => $clans,
            'tickets' => $tickets,
            'event' => $event,
            'seats' => $seats,
            'mySeats' => $mySeats,
            'clanSeats' => $clanSeats,
        ]);
    }
}
