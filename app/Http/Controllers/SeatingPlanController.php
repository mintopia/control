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
        $clans = [];
        $seatManagerClans = [];
        foreach ($request->user()->clanMemberships()->with('clan', 'role')->get() as $clanMember) {
            $clans[] = $clanMember->clan;
            $clanIds[] = $clanMember->clan->id;
            if (in_array($clanMember->role->code, ['leader', 'seatmanager'])) {
                $seatManagerClans[] = $clanMember->clan->id;
            }
        }
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
        $responsibleSeats = [];
        foreach ($allTickets as $ticket) {
            if ($ticket->user->id === $request->user()->id) {
                $tickets[0][] = $ticket;
                if ($ticket->seat_id) {
                    $mySeats[] = $ticket->seat_id;
                    $responsibleSeats[] = $ticket->seat_id;
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
                if (in_array($clanMember->clan_id, $seatManagerClans)) {
                    $responsibleSeats[] = $ticket->seat_id;
                }
            }
        }

        // Now our seat data
        $seats = [];
        foreach ($event->seatingPlans as $plan) {
            $seats[$plan->id] = $plan->getData();
        }

        $params = [
            'clans' => $clans,
            'tickets' => $tickets,
            'event' => $event,
            'seats' => $seats,
            'mySeats' => $mySeats,
            'clanSeats' => $clanSeats,
            'responsibleSeats' => $responsibleSeats,
        ];

        $view = 'seatingplans.show';
        if ($request->isXmlHttpRequest() && $request->has('plan')) {
            $plan = null;
            foreach ($event->seatingPlans as $seatingPlan) {
                if ($seatingPlan->code === $request->input('plan')) {
                    $plan = $seatingPlan;
                    break;
                }
            }
            if ($plan !== null) {
                $view = 'seatingplans._plan';
                $params['plan'] = $plan;
            }
        }

        return view($view, $params);
    }
}
