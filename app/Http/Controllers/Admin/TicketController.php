<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketProvider;
use Illuminate\Http\Request;

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

        if ($request->input('event_id')) {
            $filters->event_id = $request->input('event_id');
            $query = $query->whereEventId($filters->event_id);
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

        $params = (array)$filters;

        switch ($request->input('order')) {
            case 'created_at':
                $params['order'] = $request->input('order');
                break;
            case 'id':
            default:
                $params['order'] = 'id';
                break;
        };

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

        $tickets = $query->with(['event', 'user', 'type', 'provider', 'seat'])->paginate($params['perPage'])->appends($params);

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
}
