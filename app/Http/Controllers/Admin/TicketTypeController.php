<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteRequest;
use App\Http\Requests\Admin\TicketTypeUpdateRequest;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Http\Request;

class TicketTypeController extends Controller
{
    public function create(Event $event)
    {
        $type = new TicketType();
        $type->event()->associate($event);
        return view('admin.tickettypes.create', [
            'event' => $event,
            'type' => $type,
        ]);
    }

    public function store(TicketTypeUpdateRequest $request, Event $event)
    {
        $type = new TicketType();
        $type->event()->associate($event);
        $this->updateObject($type, $request);
        return response()->redirectToRoute('admin.events.tickettypes.show', [$event->code, $type->id])->with('successMessage', 'The ticket type has been created');
    }

    public function show(Event $event, TicketType $tickettype)
    {
        return view('admin.tickettypes.show', [
            'event' => $event,
            'type' => $tickettype
        ]);
    }

    public function edit(Event $event, TicketType $tickettype)
    {
        return view('admin.tickettypes.edit', [
            'event' => $event,
            'type' => $tickettype,
        ]);
    }

    public function update(TicketTypeUpdateRequest $request, Event $event, TicketType $tickettype)
    {
        $this->updateObject($tickettype, $request);
        return response()->redirectToRoute('admin.events.tickettypes.show', [$event->code, $tickettype->id]);
    }

    public function delete(Event $event, TicketType $tickettype)
    {
        return view('admin.tickettypes.delete', [
            'event' => $event,
            'type' => $tickettype,
        ]);
    }

    public function destroy(DeleteRequest $request, Event $event, TicketType $tickettype)
    {
        $tickettype->delete();
        return response()->redirectToRoute('admin.events.show', $event->code);
    }

    protected function updateObject(TicketType $type, Request $request)
    {
        $type->name = $request->input('name');
        $type->has_seat = (bool)$request->input('has_seat', false);
        $type->save();
    }
}
