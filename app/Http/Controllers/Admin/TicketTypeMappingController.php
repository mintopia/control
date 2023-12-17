<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketTypeMappingUpdateRequest;
use App\Models\Event;
use App\Models\TicketProvider;
use App\Models\TicketType;
use App\Models\TicketTypeMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TicketTypeMappingController extends Controller
{
    public function create(Event $event, TicketType $tickettype)
    {
        $mapping = new TicketTypeMapping();
        return view('admin.tickettypemappings.create', [
            'availableMappings' => $event->getAvailableTicketMappings(),
            'event' => $event,
            'type' => $tickettype,
            'mapping' => $mapping,
        ]);
    }

    public function store(TicketTypeMappingUpdateRequest $request, Event $event, TicketType $tickettype)
    {
        $mapping = new TicketTypeMapping();
        $mapping->type()->associate($tickettype);
        $this->updateObject($mapping, $request);
        return response()->redirectToRoute('admin.events.tickettypes.show', [$event->code, $tickettype->id])->with('successMessage', 'The ticket mapping has been added');
    }

    public function edit(Event $event, TicketType $tickettype, TicketTypeMapping $mapping)
    {
        return view('admin.tickettypemappings.edit', [
            'availableMappings' => $event->getAvailableTicketMappings($mapping),
            'event' => $event,
            'type' => $tickettype,
            'mapping' => $mapping,
        ]);
    }

    public function update(TicketTypeMappingUpdateRequest $request, Event $event, TicketType $tickettype, TicketTypeMapping $mapping)
    {
        $this->updateObject($mapping, $request);
        return response()->redirectToRoute('admin.events.tickettypes.show', [$event->code, $tickettype->id])->with('successMessage', 'The ticket mapping has been updated');
    }

    public function delete(Event $event, TicketType $tickettype, TicketTypeMapping $mapping)
    {
        return view('admin.tickettypemappings.delete', [
            'event' => $event,
            'type' => $tickettype,
            'mapping' => $mapping,
        ]);
    }

    public function destroy(Event $event, TicketType $tickettype, TicketTypeMapping $mapping)
    {
        $mapping->delete();
        return response()->redirectToRoute('admin.events.tickettypes.show', [$event->code, $tickettype->id])->with('successMessage', 'The ticket mapping has been deleted');
    }

    protected function updateObject(TicketTypeMapping $mapping, Request $request)
    {
        [$providerId, $externalId] = explode(':', $request->input('external_id'));
        $mapping->provider()->associate(TicketProvider::find($providerId));
        $mapping->external_id = $externalId;

        $types = $mapping->provider->getTicketTypes($mapping->type->event);
        $mapping->name = '';
        foreach ($types as $type) {
            if ($type->id === $externalId) {
                $mapping->name = $type->name;
                break;
            }
        }
        $mapping->save();
    }
}
