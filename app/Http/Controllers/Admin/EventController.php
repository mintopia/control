<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteRequest;
use App\Http\Requests\Admin\EventUpdateRequest;
use App\Models\Event;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\TicketProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {

        $filters = (object)[];
        $query = Event::query();

        if ($request->input('id')) {
            $filters->id = $request->input('id');
            $query = $query->whereId($filters->id);
        }

        if ($request->input('name')) {
            $filters->name = $request->input('name');
            $query = $query->where('name', 'LIKE', "%{$filters->name}%");
        }

        if ($request->input('code')) {
            $filters->code = $request->input('code');
            $query = $query->where('code', 'LIKE', "%{$filters->code}%");
        }

        if ($request->input('external_id')) {
            $filters->external_id = $request->input('external_id');
            $query = $query->whereHas('provider', function ($query) use ($filters) {
                $query->whereExternalId($filters->external_id);
            });
        }

        if ($request->input('provider_id')) {
            $filters->provider_id = $request->input('provider_id');
            $query = $query->whereHas('provider', function ($query) use ($filters) {
                $query->whereTicketProviderId($filters->provider_id);
            });
        }

        $params = (array)$filters;

        switch ($request->input('order')) {
            case 'name':
            case 'starts_at':
            case 'ends_at':
            case 'tickets_count':
            case 'created_at':
                $params['order'] = $request->input('order');
                break;
            case 'id':
            default:
                $params['order'] = 'id';
                break;
        }

        switch ($request->input('order_direction', 'asc')) {
            case 'desc':
                $params['order_direction'] = 'desc';
                break;
            case 'asc':
            default:
                $params['order_direction'] = 'asc';
        }

        $query = $query->orderBy($params['order'], $params['order_direction']);

        $params['page'] = $request->input('page', 1);
        $params['perPage'] = $request->input('perPage', 20);

        $events = $query->withCount('tickets')->paginate($params['perPage'])->appends($params);

        $providers = TicketProvider::orderBy('name', 'ASC')->get();

        return view('admin.events.index', [
            'events' => $events,
            'filters' => $filters,
            'params' => $params,
            'providers' => $providers,
        ]);
    }

    public function show(Event $event)
    {
        $seatingPlans = $event->seatingPlans()->withCount('seats')->orderBy('order', 'ASC')->get();
        $ticketTypes = $event->ticketTypes()->withCount('tickets')->get();

        return view('admin.events.show', [
            'event' => $event,
            'seatingPlans' => $seatingPlans,
            'ticketTypes' => $ticketTypes,
        ]);
    }

    public function create()
    {
        return view('admin.events.create', [
            'event' => new Event(),
        ]);
    }

    public function store(EventUpdateRequest $request)
    {
        $event = new Event;
        $this->updateObject($event, $request);
        return response()->redirectToRoute('admin.events.show', $event->code)->with('successMessage', 'The event has been created');
    }

    protected function updateObject(Event $event, Request $request): void
    {
        $event->name = $request->input('name');
        $event->starts_at = new Carbon($request->input('starts_at'));
        $event->ends_at = new Carbon($request->input('ends_at'));
        $event->boxoffice_url = $request->input('boxoffice_url');
        $event->seating_locked = (bool)$request->input('seating_locked', false);
        if ($request->input('seating_opens_at', null)) {
            $event->seating_opens_at = new Carbon($request->input('seating_opens_at'));
        } else {
            $event->seating_opens_at = null;
        }
        if ($request->input('seating_closes_at')) {
            $event->seating_closes_at = new Carbon($request->input('seating_closes_at'));
        } else {
            $event->seating_closes_at = null;
        }
        $event->draft = (bool)$request->input('draft', false);
        $event->save();
    }

    public function edit(Event $event)
    {
        return view('admin.events.edit', [
            'event' => $event,
        ]);
    }

    public function update(EventUpdateRequest $request, Event $event)
    {
        $this->updateObject($event, $request);
        return response()->redirectToRoute('admin.events.show', $event->code)->with('successMessage', 'The event has been updated');
    }

    public function destroy(DeleteRequest $request, Event $event)
    {
        $event->delete();
        return response()->redirectToRoute('admin.events.index')->with('successMessage', 'The event has been deleted');
    }

    public function delete(Event $event)
    {
        return view('admin.events.delete', [
            'event' => $event,
        ]);
    }

    public function export_tickets(Event $event)
    {
        $csv = [[
            'ID', 'Ticket Provider', 'External ID', 'Reference', 'Type', 'Nickname', 'Name', 'Email', 'Seat',
        ]];
        $event->tickets()->with(['user', 'type', 'provider', 'seat', 'user.primaryEmail'])->chunk(100, function ($chunk) use (&$csv) {
            foreach ($chunk as $ticket) {
                $csv[] = [
                    $ticket->id,
                    $ticket->provider->name,
                    $ticket->external_id,
                    $ticket->reference,
                    $ticket->type->name,
                    $ticket->user->nickname,
                    $ticket->user->name,
                    $ticket->user->primaryEmail->email,
                    $ticket->seat->label ?? '',
                ];
            }
        });

        $filename = "event-{$event->id}-tickets-" . Carbon::now()->format('YmdHis') . ".csv";
        return response()->streamDownload(function () use ($csv) {
            $handle = fopen('php://output', 'w');
            foreach ($csv as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename);
    }

    public function seats(Request $request, Event $event)
    {
        $unseated = $event->tickets()->whereDoesntHave('seat')->whereHas('type', function ($query) {
            $query->where('has_seat', true);
        })->with(['user', 'type', 'user.clanMemberships.clan'])->get();

        $seats = [];
        foreach ($event->seatingPlans as $plan) {
            $seats[$plan->id] = $plan->getData();
        }

        $currentTicket = null;
        if ($id = $request->input('ticket_id')) {
            $currentTicket = $event->tickets()->whereId($id)->first();
        }

        return view('admin.events.seats', [
            'event' => $event,
            'tickets' => $unseated,
            'seats' => $seats,
            'currentTicket' => $currentTicket,
        ]);
    }

    public function pickseat(Event $event, Ticket $ticket, Seat $seat)
    {
        if ($ticket->event_id != $event->id || $seat->plan->event_id != $event->id) {
            abort(404);
        }
        $seat->ticket()->associate($ticket);
        $seat->save();
        return response()->redirectToRoute('admin.events.seats', $event->code);
    }

    public function unseat(Event $event, Ticket $ticket)
    {
        if ($ticket->seat) {
            $seat = $ticket->seat;
            $seat->ticket()->disassociate($ticket);
            $seat->save();
        }
        return response()->redirectToRoute('admin.events.seats', $event->code);
    }
}
