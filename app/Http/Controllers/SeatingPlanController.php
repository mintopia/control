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
        foreach ($allTickets as $ticket) {
            if ($ticket->user->id === $request->user()->id) {
                $tickets[0][] = $ticket;
                continue;
            }
            foreach ($ticket->user->clanMemberships as $clanMember) {
                if (!isset($tickets[$clanMember->clan_id])) {
                    $tickets[$clanMember->clan_id] = [];
                }
                $tickets[$clanMember->clan_id][] = $ticket;
            }
        }

        // Now our seat data - TODO: Cache This
        $seats = [];
        $planIds = $event->seatingPlans()->pluck('id');
        $unsortedSeats = Seat::whereIn('seating_plan_id', $planIds)
            ->with(['ticket', 'ticket.user', 'plan'])
            ->orderBy('row', 'ASC')
            ->orderBy('number', 'ASC')
            ->get();
        foreach ($unsortedSeats as $seat) {
            if (!isset($seats[$seat->seating_plan_id])) {
                $seats[$seat->seating_plan_id] = [];
            }
            $seats[$seat->seating_plan_id][] = $seat;
        }

        return view('seatingplans.show', [
            'clans' => $clans,
            'tickets' => $tickets,
            'event' => $event,
            'seats' => $seats,
        ]);
    }
}
