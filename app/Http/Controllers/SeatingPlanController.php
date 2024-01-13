<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Seat;
use App\Models\Ticket;
use Illuminate\Http\Request;

class SeatingPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::query();
        if (!$request->user()->hasRole('admin')) {
            $query = $query->whereDraft(false);
        }
        $events = $query->orderBy('starts_at', 'DESC')->with('seatingPlans')->paginate();
        return view('seatingplans.index', [
            'events' => $events,
        ]);
    }

    public function show(Request $request, Event $event, ?Ticket $ticket = null)
    {
        if ($event->seating_locked) {
            $request->session()->now('infoMessage', 'Seating is locked');
        }

        if ($ticket && (!$ticket->canPickSeat() || !$ticket->canBeManagedBy($request->user()))) {
            return response()->redirectToRoute('seatingplans.show', $event->code)->with('errorMessage', 'You cannot pick a seat for this ticket');
        }

        $currentTicket = $ticket;

        // Get clans and tickets (left-hand sidebar)
        $seatManagerClans = [];
        $allClans = [];
        foreach ($request->user()->clanMemberships()->with('role')->get() as $clanMember) {
            $allClans[] = $clanMember->clan_id;
            if (in_array($clanMember->role->code, ['leader', 'seatmanager'])) {
                $seatManagerClans[] = $clanMember->clan_id;
            }
        }

        $allTickets = $event->tickets()
            ->whereHas('type', function ($query) {
                $query->where('has_seat', true);
            })
            ->where(function ($query) use ($allClans, $request) {
                $query
                    ->whereUserId($request->user()->id)
                    ->orWhere(function ($query) use ($allClans) {
                        $query->whereHas('user.clanmemberships', function ($query) use ($allClans) {
                            $query->whereIn('clan_id', $allClans);
                        });
                    });
            })
            ->with(['type', 'seat', 'event', 'user' => function ($query) {
                $query->orderBy('nickname', 'ASC');
            }, 'user.clanMemberships'])
            ->get();


        $clanSeats = [];
        $mySeats = [];
        $responsibleTickets = [];
        $responsibleSeats = [];

        foreach ($allTickets as $ticket) {
            if ($ticket->user->id === $request->user()->id) {
                array_unshift($responsibleTickets, $ticket);
                if ($ticket->seat) {
                    $mySeats[] = $ticket->seat->id;
                    $responsibleSeats[] = $ticket->seat->id;
                }
                continue;
            }
            foreach ($ticket->user->clanMemberships as $clanMember) {
                if ($ticket->seat) {
                    $clanSeats[] = $ticket->seat->id;
                }

                if (in_array($clanMember->clan_id, $seatManagerClans)) {
                    $responsibleTickets[] = $ticket;
                    if ($ticket->seat) {
                        $responsibleSeats[] = $ticket->seat->id;
                    }
                }
            }
        }

        // Now our seat data
        $seats = [];
        foreach ($event->seatingPlans as $plan) {
            $seats[$plan->id] = $plan->getData();
        }

        $myClans = [];
        foreach($request->user()->clanMemberships as $clanMembership){
            $myClans[] = $clanMembership->clan->code;
        }

        $params = [
            'allTickets' => $allTickets,
            'event' => $event,
            'seats' => $seats,
            'mySeats' => $mySeats,
            'myClans' => $myClans,
            'clanSeats' => $clanSeats,
            'responsibleSeats' => $responsibleSeats,
            'responsibleTickets' => $responsibleTickets,
            'currentTicket' => $currentTicket,
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

    public function select(Request $request, Event $event, Ticket $ticket, Seat $seat)
    {
        if ($seat->plan->event_id !== $event->id) {
            abort(404);
        }
        if ($ticket->event_id !== $event->id) {
            abort(404);
        }
        if (!$ticket->canPickSeat() || !$ticket->canBeManagedBy($request->user())) {
            return response()->redirectToRoute('seatingplans.show', $event->code)->with('errorMessage', 'You cannot pick a seat for this ticket')->withFragment("tab-plan-{$seat->plan->code}");
        }

        if (!$seat->canPick($request->user())) {
            return response()->redirectToRoute('seatingplans.show', [$event->code, $ticket->id])->with('errorMessage', "That seat is not available")->withFragment("tab-plan-{$seat->plan->code}");
        }

        if ($ticket->seat) {
            $oldSeat = $ticket->seat;
            $oldSeat->ticket()->disassociate();
            if ($oldSeat->seating_plan_id !== $seat->seating_plan_id) {
                $oldSeat->save();
            } else {
                $oldSeat->saveQuietly();
            }
        }

        $seat->ticket()->associate($ticket);
        $seat->save();
        return response()->redirectToRoute('seatingplans.show', $event->code)->withFragment("tab-plan-{$seat->plan->code}");
    }

    public function unseat(Request $request, Event $event, Ticket $ticket)
    {
        if (!$ticket->canPickSeat() || !$ticket->canBeManagedBy($request->user())) {
            return response()->redirectToRoute('seatingplans.show', $event->code)->with('errorMessage', 'You cannot unseat this ticket')->withFragment("tab-plan-{$seat->plan->code}");
        }

        if ($ticket->seat) {
            $seat = $ticket->seat;
            $seat->ticket()->disassociate();
            $seat->save();
        }

        return response()->redirectToRoute('seatingplans.show', $event->code)->withFragment("tab-plan-{$seat->plan->code}");
    }
}
