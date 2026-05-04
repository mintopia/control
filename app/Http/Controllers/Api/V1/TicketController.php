<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Event;
use App\Models\Ticket;
use App\Transformers\V1\TicketTransformer;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 20);

        $query = Ticket::query()
            ->with(['user.primaryEmail', 'seat', 'type', 'provider', 'event'])
            ->orderBy('id', 'asc');

        if ($code = $request->input('event')) {
            $event = Event::where('code', $code)->first();
            if ($event === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('event_id', $event->id);
            }
        }

        $appends = ['perPage' => $perPage];
        if ($request->filled('event')) {
            $appends['event'] = $request->input('event');
        }

        $tickets = $query->paginate($perPage)->appends($appends);

        return fractal($tickets, new TicketTransformer)->respond();
    }

    protected function apiKey(Request $request): ?ApiKey
    {
        $user = $request->user();

        return $user instanceof ApiKey ? $user : null;
    }
}
