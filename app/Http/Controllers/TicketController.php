<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketTransferRequest;
use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = $request
            ->user()
            ->tickets();

        if (!$request->user()->hasRole('admin')) {
            $query->whereHas('event', function ($query) {
                $query->whereDraft(false);
            });
        }

        $sortable = ['reference', 'type', 'seat', 'event'];
        $order = in_array($request->input('order'), $sortable, true)
            ? $request->input('order')
            : 'event';

        $direction = strtolower((string) $request->input('order_direction'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = $order === 'event' ? 'desc' : 'asc';
        }

        switch ($order) {
            case 'reference':
                $query->orderBy('tickets.reference', $direction);
                break;
            case 'type':
                $query->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
                    ->orderBy('ticket_types.name', $direction);
                break;
            case 'seat':
                $query->leftJoin('seats', 'seats.ticket_id', '=', 'tickets.id')
                    ->orderBy('seats.row', $direction)
                    ->orderBy('seats.number', $direction);
                break;
            case 'event':
            default:
                $query->join('events', 'tickets.event_id', '=', 'events.id')
                    ->orderBy('events.starts_at', $direction);
                break;
        }

        $params = [
            'order' => $order,
            'order_direction' => $direction,
        ];

        $tickets = $query->with(['event' => function ($query) {
            $query->orderBy('starts_at', 'DESC');
        }, 'type', 'seat'])
            ->select('tickets.*')
            ->orderBy('tickets.id')
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
