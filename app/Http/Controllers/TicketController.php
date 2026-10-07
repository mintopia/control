<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketIndexRequest;
use App\Http\Requests\TicketTransferRequest;
use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(TicketIndexRequest $request)
    {
        $order = $request->input('order', 'event');
        $direction = $request->input('order_direction', $order === 'event' ? 'desc' : 'asc');

        $query = $request
            ->user()
            ->tickets();

        if (!$request->user()->hasRole('admin')) {
            $query->whereHas('event', function ($query) {
                $query->whereDraft(false);
            });
        }

        switch ($order) {
            case 'reference':
                $query->orderBy('reference', $direction);
                break;
            case 'type':
                $query->withAggregate('type', 'name')
                    ->orderBy('type_name', $direction);
                break;
            case 'seat':
                $query->withAggregate('seat', 'row')
                    ->withAggregate('seat', 'number')
                    ->orderBy('seat_row', $direction)
                    ->orderBy('seat_number', $direction);
                break;
            case 'event':
            default:
                $query->withAggregate('event', 'starts_at')
                    ->orderBy('event_starts_at', $direction);
                break;
        }

        $params = [
            'order' => $order,
            'order_direction' => $direction,
        ];

        $tickets = $query->with(['event', 'type', 'seat'])
            ->orderBy('id')
            ->paginate()
            ->appends($params);

        return view('tickets.index', [
            'tickets' => $tickets,
            'params' => $params,
        ]);
    }

    public function show(Request $request, Ticket $ticket)
    {
        return view('tickets.show', [
            'ticket' => $ticket,
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        if (Setting::fetch('disable-ticket-transfers')) {
            return response()->redirectToRoute('tickets.show', $ticket->id)->with('errorMessage', 'Ticket transfers are disabled.');
        }
        if ($request->has('generate')) {
            $ticket->generateTransferCode();
            return response()->redirectToRoute('tickets.show', $ticket->id)->with('successMessage', 'A new transfer code has been generated');
        } elseif ($request->has('remove')) {
            $ticket->transfer_code = null;
            $ticket->save();
            return response()->redirectToRoute('tickets.show', $ticket->id)->with('successMessage', 'The transfer code has been removed');
        }
        return response()->redirectToRoute('tickets.show', $ticket->id);
    }

    public function transfer(TicketTransferRequest $request)
    {
        if (Setting::fetch('disable-ticket-transfers')) {
            return response()->redirectToRoute('tickets.index')->with('errorMessage', 'Ticket transfers are disabled.');
        }
        $ticket = Ticket::whereTransferCode($request->input('code'))->first();
        $ticket->user()->associate($request->user());
        $ticket->transfer_code = null;
        $ticket->save();
        return response()->redirectToRoute('tickets.show', $ticket->id)->with('successMessage', 'The ticket has been transferred to your account');
    }
}
