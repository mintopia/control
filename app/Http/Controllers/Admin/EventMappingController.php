<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventMappingUpdateRequest;
use App\Models\Event;
use App\Models\EventMapping;
use App\Models\TicketProvider;
use Illuminate\Http\Request;

class EventMappingController extends Controller
{
    public function create(Event $event)
    {
        $mapping = new EventMapping();
        return view('admin.eventmappings.create', [
            'availableMappings' => $event->getAvailableEventMappings(),
            'event' => $event,
            'mapping' => $mapping,
        ]);
    }

    public function store(EventMappingUpdateRequest $request, Event $event)
    {
        $mapping = new EventMapping();
        $mapping->event()->associate($event);
        $this->updateObject($mapping, $request);
        return response()->redirectToRoute('admin.events.show', $event->code)->with('successMessage', 'The event mapping has been added');
    }

    protected function updateObject(EventMapping $mapping, Request $request)
    {
        [$providerId, $externalId] = explode(':', $request->input('external_id'));
        $mapping->provider()->associate(TicketProvider::find($providerId));
        $mapping->external_id = $externalId;

        $events = $mapping->provider->getEvents();
        $mapping->name = '';
        foreach ($events as $event) {
            if ($event->id == $externalId) {
                $mapping->name = $event->name;
                break;
            }
        }
        if (!$mapping->name) {
            $mapping->name = $mapping->event->name;
        }
        $mapping->save();
    }

    public function edit(Event $event, EventMapping $mapping)
    {
        return view('admin.eventmappings.edit', [
            'availableMappings' => $event->getAvailableEventMappings($mapping),
            'event' => $event,
            'mapping' => $mapping,
        ]);
    }

    public function update(EventMappingUpdateRequest $request, Event $event, EventMapping $mapping)
    {
        $this->updateObject($mapping, $request);
        return response()->redirectToRoute('admin.events.show', $event->code)->with('successMessage', 'The event mapping has been updated');
    }

    public function destroy(Event $event, EventMapping $mapping)
    {
        $mapping->delete();
        return response()->redirectToRoute('admin.events.show', $event->code)->with('successMessage', 'The event mapping has been deleted');
    }

    public function delete(Event $event, EventMapping $mapping)
    {
        return view('admin.eventmappings.delete', [
            'event' => $event,
            'mapping' => $mapping,
        ]);
    }
}
