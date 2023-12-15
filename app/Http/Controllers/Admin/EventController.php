<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TicketProvider;
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
            $query = $query->whereHas('provider', function($query) use ($filters) {
                $query->whereExternalId($filters->external_id);
            });
        }

        if ($request->input('provider_id')) {
            $filters->provider_id = $request->input('provider_id');
            $query = $query->whereHas('provider', function($query) use ($filters) {
                $query->whereTicketProviderId($filters->provider_id);
            });
        }

        $params = (array)$filters;

        switch ($request->input('order')) {
            case 'name':
            case 'code':
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
        $providers = $event->providers()->get();

        $totalProviders = TicketProvider::whereEnabled(true)->count();
        $canAddProvider = ($totalProviders - count($providers)) > 0;

        return view('admin.events.show', [
            'event' => $event,
            'seatingPlans' => $seatingPlans,
            'ticketTypes' => $ticketTypes,
            'providers' => $providers,
            'canAddProvider' => $canAddProvider,
        ]);
    }
}
