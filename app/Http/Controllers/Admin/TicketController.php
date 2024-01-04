<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketImportRequest;
use App\Http\Requests\Admin\TicketUpdateRequest;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketProvider;
use App\Models\TicketType;
use Illuminate\Http\Request;
use function App\makeCode;

class TicketController extends Controller
{
    public function index(Request $request)
    {

        $filters = (object)[];
        $query = Ticket::query();

        if ($request->input('id')) {
            $filters->id = $request->input('id');
            $query = $query->whereId($filters->id);
        }

        if ($request->input('reference')) {
            $filters->reference = $request->input('reference');
            $query = $query->whereId($filters->reference);
        }

        if ($request->input('user_id')) {
            $filters->user_id = $request->input('user_id');
            $query = $query->whereUserId($filters->user_id);
        }

        if ($request->input('event')) {
            $filters->event = $request->input('event');
            $query = $query->whereHas('event', function ($query) use ($filters) {
                $query->whereCode($filters->event);
            });
        }

        if ($request->input('ticket_type_id')) {
            $filters->ticket_type_id = $request->input('ticket_type_id');
            $query = $query->whereTicketTypeId($filters->ticket_type_id);
        }

        if ($request->input('external_id')) {
            $filters->external_id = $request->input('external_id');
            $query = $query->whereExternalId($filters->external_id);
        }

        if ($request->input('provider_id')) {
            $filters->provider_id = $request->input('provider_id');
            $query = $query->whereTicketProviderId($filters->provider_id);
        }

        if ($request->input('seat')) {
            $filters->seat = $request->input('seat');
            $query = $query->whereHas('seat', function ($query) use ($filters) {
                $query->whereLabel($filters->seat);
            });
        }

        if ($request->input('original_email')) {
            $filters->original_email = $request->input('original_email');
            $query = $query->whereOriginalEmail($filters->original_email);
        }

        $params = (array)$filters;

        switch ($request->input('order_direction', 'asc')) {
            case 'desc':
                $params['order_direction'] = 'desc';
                break;
            case 'asc':
            default:
                $params['order_direction'] = 'asc';
        }

        switch ($request->input('order')) {
            case 'created_at':
            case 'external_id':
            case 'reference':
                $params['order'] = $request->input('order');
                $query = $query->orderBy($params['order'], $params['order_direction']);
                break;

            case 'event':
                $params['order'] = 'event';
                $query = $query
                    ->join('events', 'tickets.event_id', '=', 'events.id')
                    ->orderBy('events.name', $params['order_direction']);
                break;

            case 'type':
                $params['order'] = 'type';
                $query = $query
                    ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
                    ->orderBy('ticket_types.name', $params['order_direction']);
                break;

            case 'user':
                $params['order'] = 'user';
                $query = $query
                    ->join('users', 'tickets.user_id', '=', 'users.id')
                    ->orderBy('users.nickname', $params['order_direction']);
                break;

            case 'seat':
                $params['order'] = 'seat';
                $query = $query
                    ->leftJoin('seats', 'seats.ticket_id', '=', 'tickets.id')
                    ->orderBy('seats.row', $params['order_direction'])
                    ->orderBy('seats.number', $params['order_direction']);
                break;

            case 'id':
            default:
                $params['order'] = 'id';
                $query = $query->orderBy($params['order'], $params['order_direction']);
                break;
        }


        $params['page'] = $request->input('page', 1);
        $params['perPage'] = $request->input('perPage', 20);

        $tickets = $query
            ->with(['event', 'user', 'type', 'provider', 'seat'])
            ->select('tickets.*')
            ->paginate($params['perPage'])
            ->appends($params);

        $events = Event::orderBy('starts_at', 'DESC')->get();
        $providers = TicketProvider::orderBy('name', 'ASC')->get();

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'params' => $params,
            'events' => $events,
            'providers' => $providers,
        ]);
    }

    public function create(Request $request)
    {
        // TODO: Make the UI better and load in ticket types from when the event is selected, so we won't need the event
        if (!$request->has('event') || !$event = Event::whereCode($request->input('event'))->first()) {
            abort(404);
        }

        $ticket = new Ticket();
        $ticket->event()->associate($event);
        return view('admin.tickets.create', [
            'ticket' => $ticket,
        ]);
    }

    public function store(TicketUpdateRequest $request)
    {
        $type = TicketType::find($request->input('ticket_type_id'));
        $provider = TicketProvider::whereCode('internal')->first();

        $ticket = new Ticket();
        $ticket->event()->associate($type->event);
        $ticket->provider()->associate($provider);

        $ticket->external_id = makeCode(8);
        $ticket->name = $type->name;
        $ticket->qrcode = 'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=' . $ticket->external_id;

        $this->updateObject($ticket, $request);
        return response()->redirectToRoute('admin.tickets.show', $ticket->id)->with('successMessage', 'The ticket has been added');
    }

    protected function updateObject(Ticket $ticket, Request $request)
    {
        $ticket->reference = $request->input('reference');
        $ticket->user_id = $request->input('user_id');
        $ticket->ticket_type_id = $request->input('ticket_type_id');
        $ticket->save();
    }

    public function show(Ticket $ticket)
    {
        return view('admin.tickets.show', [
            'ticket' => $ticket,
        ]);
    }

    public function edit(Ticket $ticket)
    {
        return view('admin.tickets.edit', [
            'ticket' => $ticket,
        ]);
    }

    public function update(TicketUpdateRequest $request, Ticket $ticket)
    {
        $this->updateObject($ticket, $request);
        return response()->redirectToRoute('admin.tickets.show', $ticket->id)->with('successMessage', 'The ticket has been updated');
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();
        return response()->redirectToRoute('admin.tickets.index')->with('successMessage', 'The ticket has been deleted');
    }

    public function delete(Ticket $ticket)
    {
        return view('admin.tickets.delete', [
            'ticket' => $ticket,
        ]);
    }

    public function import()
    {
        return view('admin.tickets.import');
    }

    public function import_show(TicketImportRequest $request)
    {
        $csv = $request->file('csv')->get();
        $imports = Ticket::import($csv);
        $request->session()->flash('imports', $imports);
        return view('admin.tickets.import_show', [
            'imports' => $imports,
        ]);
    }

    public function import_process(Request $request)
    {
        // TODO: Process Import
        $imports = $request->session()->get('imports');
        foreach ($imports as $import) {
            Ticket::createFromImport($import);
        }

        return response()->redirectToRoute('admin.tickets.index')->with('successMessage', 'The tickets have been imported');
    }

}
